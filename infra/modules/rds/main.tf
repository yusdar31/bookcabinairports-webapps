resource "aws_db_subnet_group" "this" {
  name       = "${var.name_prefix}-db-subnets"
  subnet_ids = var.private_subnet_ids

  tags = merge(var.tags, {
    Name = "${var.name_prefix}-db-subnets"
  })
}

resource "aws_db_instance" "this" {
  identifier              = "${var.name_prefix}-db"
  engine                  = "mysql"
  engine_version          = "8.0"
  instance_class          = var.instance_class
  allocated_storage       = var.allocated_storage
  storage_type            = var.storage_type
  db_name                 = var.db_name
  username                = var.db_username
  password                = var.db_password
  db_subnet_group_name    = aws_db_subnet_group.this.name
  vpc_security_group_ids  = [var.security_group_id]
  multi_az                = var.multi_az
  publicly_accessible     = false
  backup_retention_period = var.backup_retention
  skip_final_snapshot     = true
  deletion_protection     = false
  apply_immediately       = true

  tags = merge(var.tags, {
    Name = "${var.name_prefix}-db"
  })
}
