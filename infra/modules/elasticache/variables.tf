variable "name_prefix" { type = string }
variable "node_type" { type = string }
variable "subnet_ids" { type = list(string) }
variable "security_group_id" { type = string }
variable "replicas" { type = number }
variable "tags" { type = map(string) }
