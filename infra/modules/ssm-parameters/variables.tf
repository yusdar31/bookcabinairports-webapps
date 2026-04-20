variable "name_prefix" {
  type = string
}

variable "db_password" {
  type      = string
  sensitive = true
}

variable "db_password_parameter_name" {
  type = string
}

variable "tags" {
  type = map(string)
}
