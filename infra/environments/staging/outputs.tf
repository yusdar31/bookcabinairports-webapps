output "alb_dns_name" {
  value = module.stack.alb_dns_name
}

output "cloudfront_domain_name" {
  value = module.stack.cloudfront_domain_name
}

output "ec2_public_ip" {
  value = module.stack.ec2_public_ip
}

output "rds_endpoint" {
  value = module.stack.rds_endpoint
}

output "redis_endpoint" {
  value = module.stack.redis_endpoint
}

output "api_gateway_invoke_url" {
  value = module.stack.api_gateway_invoke_url
}

output "sqs_queue_url" {
  value = module.stack.sqs_queue_url
}

output "budget_name" {
  value = module.stack.budget_name
}

output "dashboard_name" {
  value = module.stack.dashboard_name
}

output "rds_identifier" {
  value = module.stack.rds_identifier
}

output "sqs_dlq_name" {
  value = module.stack.sqs_dlq_name
}
