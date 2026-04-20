output "alb_dns_name" {
  value = module.alb.dns_name
}

output "cloudfront_domain_name" {
  value = module.cdn.domain_name
}

output "ec2_public_ip" {
  value = module.ec2.public_ip
}

output "rds_endpoint" {
  value = module.rds.db_endpoint
}

output "redis_endpoint" {
  value = try(module.elasticache[0].redis_endpoint, "")
}

output "api_gateway_invoke_url" {
  value = module.api_gateway.invoke_url
}

output "sqs_queue_url" {
  value = module.sqs.queue_url
}

output "budget_name" {
  value = module.monitoring.budget_name
}

output "dashboard_name" {
  value = module.monitoring.dashboard_name
}

output "rds_identifier" {
  value = module.rds.db_instance_id
}

output "sqs_dlq_name" {
  value = module.sqs.dlq_name
}

output "db_password_parameter_name" {
  value = module.ssm_parameters.db_password_parameter_name
}
