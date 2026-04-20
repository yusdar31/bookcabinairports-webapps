config = {
  environment          = "production"
  aws_region           = "ap-southeast-1"
  project_name         = "bookcabin"
  project_tag_value    = "bookcabin"
  vpc_cidr             = "10.0.0.0/16"
  public_subnet_cidrs  = ["10.0.1.0/24", "10.0.2.0/24"]
  private_subnet_cidrs = ["10.0.10.0/24", "10.0.20.0/24"]
  availability_zones   = ["ap-southeast-1a", "ap-southeast-1b"]
  allowed_ssh_cidr     = "CHANGE_ME/32"

  use_ecs              = true
  use_ec2              = true
  ec2_instance_type    = "t3.micro"
  ec2_ami_id           = ""
  ec2_root_volume_size = 8
  public_key           = "CHANGE_ME"

  db_name              = "bookcabin"
  db_username          = "admin"
  db_password          = "CHANGE_ME"
  db_instance_class    = "db.t3.medium"
  db_allocated_storage = 200
  db_storage_type      = "gp3"
  db_multi_az          = true
  db_backup_retention  = 7

  redis_node_type = "cache.t3.micro"
  redis_multi_az  = true
  redis_replicas  = 1

  enable_waf               = true
  enable_shield            = true
  enable_autoscaling       = true
  log_retention_days       = 30
  enable_anomaly_detection = true

  ecr_repository_app = "bookcabin-app"
  ecr_repository_api = "bookcabin-api"

  budget_limit_usd    = "400"
  budget_name         = "bookcabin-production-budget"
  dashboard_name      = "bookcabin-production-dashboard"
  sqs_queue_name      = "bookcabin-production-ota-webhook-queue"
  sqs_dlq_name        = "bookcabin-production-ota-webhook-dlq"
  budget_alert_emails = ["alerts@example.com"]
  github_repository   = ""
}
