variable "name_prefix" { type = string }
variable "db_name" { type = string }
variable "db_username" { type = string }
variable "db_password" {
  type      = string
  sensitive = true
}
variable "instance_class" { type = string }
variable "allocated_storage" { type = number }
variable "storage_type" { type = string }
variable "multi_az" { type = bool }
variable "backup_retention" { type = number }
variable "private_subnet_ids" { type = list(string) }
variable "security_group_id" { type = string }
variable "tags" { type = map(string) }
