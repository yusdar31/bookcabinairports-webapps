variable "name_prefix" {
  type = string
}

variable "budget_name" {
  type = string
}

variable "dashboard_name" {
  type = string
}

variable "budget_limit_usd" {
  type = string
}

variable "project_tag_value" {
  type = string
}

variable "budget_alert_emails" {
  type = list(string)
}

variable "ec2_instance_id" {
  type = string
}

variable "rds_instance_id" {
  type = string
}

variable "redis_cluster_id" {
  type = string
}

variable "dlq_name" {
  type = string
}

variable "tags" {
  type = map(string)
}
