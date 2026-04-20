resource "aws_ssm_parameter" "db_password" {
  name      = var.db_password_parameter_name
  type      = "SecureString"
  value     = var.db_password
  overwrite = true

  tags = merge(var.tags, {
    Name = "${var.name_prefix}-db-password"
  })
}
