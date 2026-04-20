config = {
  environment          = "staging"
  aws_region           = "ap-southeast-1"
  project_name         = "bookcabin"
  project_tag_value    = "bookcabin"
  vpc_cidr             = "10.0.0.0/16"
  public_subnet_cidrs  = ["10.0.1.0/24", "10.0.2.0/24"]
  private_subnet_cidrs = ["10.0.10.0/24", "10.0.20.0/24"]
  availability_zones   = ["ap-southeast-1a", "ap-southeast-1b"]
  allowed_ssh_cidr     = "CHANGE_ME/32"

  use_ecs              = false
  use_ec2              = true
  ec2_instance_type    = "t3.micro"
  ec2_ami_id           = ""
  ec2_root_volume_size = 8
  public_key           = "CHANGE_ME"

  db_name              = "bookcabin"
  db_username          = "admin"
  db_password          = "CHANGE_ME"
  db_instance_class    = "db.t3.micro"
  db_allocated_storage = 20
  db_storage_type      = "gp2"
  db_multi_az          = false
  db_backup_retention  = 1

  redis_node_type = "cache.t3.micro"
  redis_multi_az  = false
  redis_replicas  = 0

  enable_waf               = false
  enable_shield            = false
  enable_autoscaling       = false
  log_retention_days       = 7
  enable_anomaly_detection = false

  ecr_repository_app = "bookcabin-app"
  ecr_repository_api = "bookcabin-api"

  budget_limit_usd    = "25"
  budget_name         = "bookcabin-staging-budget"
  dashboard_name      = "bookcabin-staging-dashboard"
  sqs_queue_name      = "bookcabin-staging-ota-webhook-queue"
  sqs_dlq_name        = "bookcabin-staging-ota-webhook-dlq"
  budget_alert_emails = ["alerts@example.com"]
  github_repository   = ""
}
