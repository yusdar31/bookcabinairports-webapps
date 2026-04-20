variable "enabled" { type = bool }
variable "name_prefix" { type = string }
variable "instance_type" { type = string }
variable "ami_id" { type = string }
variable "subnet_id" { type = string }
variable "security_group_id" { type = string }
variable "root_volume_size" { type = number }
variable "public_key" { type = string }
variable "target_group_arn" { type = string }
variable "ecr_registry" { type = string }
variable "app_image" { type = string }
variable "api_image" { type = string }
variable "db_host" { type = string }
variable "db_name" { type = string }
variable "db_username" { type = string }
variable "db_password" {
  type      = string
  sensitive = true
}
variable "db_password_parameter_name" { type = string }
variable "redis_host" { type = string }
variable "sqs_queue_url" { type = string }
variable "aws_region" { type = string }
variable "tags" { type = map(string) }
