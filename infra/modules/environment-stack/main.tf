data "aws_caller_identity" "current" {}

locals {
  name_prefix = "${var.config.project_name}-${var.config.environment}"

  tags = {
    Project     = var.config.project_tag_value
    Environment = var.config.environment
    ManagedBy   = "terraform"
  }
}

module "networking" {
  source = "../networking"

  name_prefix          = local.name_prefix
  vpc_cidr             = var.config.vpc_cidr
  availability_zones   = var.config.availability_zones
  public_subnet_cidrs  = var.config.public_subnet_cidrs
  private_subnet_cidrs = var.config.private_subnet_cidrs
  allowed_ssh_cidr     = var.config.allowed_ssh_cidr
  tags                 = local.tags
}

module "alb" {
  source = "../alb"

  name_prefix       = local.name_prefix
  vpc_id            = module.networking.vpc_id
  public_subnet_ids = module.networking.public_subnet_ids
  alb_sg_id         = module.networking.alb_sg_id
  health_check_path = "/health"
  tags              = local.tags
}

module "ecr" {
  source = "../ecr"

  name_prefix          = local.name_prefix
  app_repository_name  = var.config.ecr_repository_app
  api_repository_name  = var.config.ecr_repository_api
  image_tag_mutability = "MUTABLE"
  tags                 = local.tags
}

module "sqs" {
  source = "../sqs"

  queue_name = var.config.sqs_queue_name
  dlq_name   = var.config.sqs_dlq_name
  tags       = local.tags
}

module "rds" {
  source = "../rds"

  name_prefix        = local.name_prefix
  db_name            = var.config.db_name
  db_username        = var.config.db_username
  db_password        = var.config.db_password
  instance_class     = var.config.db_instance_class
  allocated_storage  = var.config.db_allocated_storage
  storage_type       = var.config.db_storage_type
  multi_az           = var.config.db_multi_az
  backup_retention   = var.config.db_backup_retention
  private_subnet_ids = module.networking.private_subnet_ids
  security_group_id  = module.networking.rds_sg_id
  tags               = local.tags
}

module "ssm_parameters" {
  source = "../ssm-parameters"

  name_prefix                = local.name_prefix
  db_password                = var.config.db_password
  db_password_parameter_name = try(var.config.db_password_parameter_name, "/${local.name_prefix}/database/password")
  tags                       = local.tags
}

module "elasticache" {
  source = "../elasticache"
  count  = var.config.redis_node_type != "" ? 1 : 0

  name_prefix       = local.name_prefix
  node_type         = var.config.redis_node_type
  subnet_ids        = module.networking.private_subnet_ids
  security_group_id = module.networking.redis_sg_id
  replicas          = var.config.redis_replicas
  tags              = local.tags
}

module "ec2" {
  source = "../ec2"

  enabled                    = var.config.use_ec2
  name_prefix                = local.name_prefix
  instance_type              = var.config.ec2_instance_type
  ami_id                     = try(var.config.ec2_ami_id, "")
  subnet_id                  = module.networking.public_subnet_ids[0]
  security_group_id          = module.networking.ec2_sg_id
  root_volume_size           = try(var.config.ec2_root_volume_size, 8)
  public_key                 = var.config.public_key
  target_group_arn           = module.alb.target_group_arn
  ecr_registry               = "${data.aws_caller_identity.current.account_id}.dkr.ecr.${var.config.aws_region}.amazonaws.com"
  app_image                  = "${module.ecr.app_repository_url}:latest"
  api_image                  = "${module.ecr.api_repository_url}:latest"
  db_host                    = module.rds.db_endpoint
  db_name                    = var.config.db_name
  db_username                = var.config.db_username
  db_password                = var.config.db_password
  db_password_parameter_name = module.ssm_parameters.db_password_parameter_name
  redis_host                 = try(module.elasticache[0].redis_endpoint, "")
  sqs_queue_url              = module.sqs.queue_url
  aws_region                 = var.config.aws_region
  tags                       = local.tags
}

module "api_gateway" {
  source = "../api-gateway"

  name_prefix = local.name_prefix
  aws_region  = var.config.aws_region
  queue_arn   = module.sqs.queue_arn
  queue_name  = module.sqs.queue_name
  tags        = local.tags
}

module "cdn" {
  source = "../cdn"

  name_prefix     = local.name_prefix
  origin_dns_name = module.alb.dns_name
  tags            = local.tags
}

module "monitoring" {
  source = "../monitoring"

  budget_name         = var.config.budget_name
  dashboard_name      = var.config.dashboard_name
  name_prefix         = local.name_prefix
  budget_limit_usd    = var.config.budget_limit_usd
  project_tag_value   = var.config.project_tag_value
  budget_alert_emails = var.config.budget_alert_emails
  ec2_instance_id     = module.ec2.instance_id
  rds_instance_id     = module.rds.db_instance_id
  redis_cluster_id    = try(module.elasticache[0].cluster_id, "")
  dlq_name            = module.sqs.dlq_name
  tags                = local.tags
}
