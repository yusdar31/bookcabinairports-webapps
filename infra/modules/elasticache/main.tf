resource "aws_elasticache_subnet_group" "this" {
  name       = "${var.name_prefix}-redis-subnets"
  subnet_ids = var.subnet_ids

  tags = merge(var.tags, {
    Name = "${var.name_prefix}-redis-subnets"
  })
}

resource "aws_elasticache_cluster" "this" {
  cluster_id         = "${var.name_prefix}-redis"
  engine             = "redis"
  engine_version     = "7.1"
  node_type          = var.node_type
  num_cache_nodes    = 1 + var.replicas
  port               = 6379
  subnet_group_name  = aws_elasticache_subnet_group.this.name
  security_group_ids = [var.security_group_id]
  maintenance_window = "sun:18:00-sun:19:00"
  apply_immediately  = true

  tags = merge(var.tags, {
    Name = "${var.name_prefix}-redis"
  })
}
