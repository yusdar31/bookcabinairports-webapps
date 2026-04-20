resource "aws_cloudwatch_dashboard" "this" {
  dashboard_name = var.dashboard_name

  dashboard_body = jsonencode({
    widgets = [for w in [
      {
        type   = "metric"
        width  = 12
        height = 6
        properties = {
          title   = "EC2 CPU Utilization"
          region  = "ap-southeast-1"
          stat    = "Average"
          period  = 300
          metrics = [["AWS/EC2", "CPUUtilization", "InstanceId", var.ec2_instance_id]]
        }
      },
      {
        type   = "metric"
        width  = 12
        height = 6
        properties = {
          title   = "RDS Connections"
          region  = "ap-southeast-1"
          stat    = "Average"
          period  = 300
          metrics = [["AWS/RDS", "DatabaseConnections", "DBInstanceIdentifier", var.rds_instance_id]]
        }
      },
      var.redis_cluster_id != "" ? {
        type   = "metric"
        width  = 12
        height = 6
        properties = {
          title   = "Redis CPU"
          region  = "ap-southeast-1"
          stat    = "Average"
          period  = 300
          metrics = [["AWS/ElastiCache", "CPUUtilization", "CacheClusterId", var.redis_cluster_id]]
        }
      } : null,
      {
        type   = "metric"
        width  = 12
        height = 6
        properties = {
          title   = "SQS DLQ Visible Messages"
          region  = "ap-southeast-1"
          stat    = "Sum"
          period  = 300
          metrics = [["AWS/SQS", "ApproximateNumberOfMessagesVisible", "QueueName", var.dlq_name]]
        }
      }
    ] : w if w != null]
  })
}

resource "aws_budgets_budget" "this" {
  name         = var.budget_name
  budget_type  = "COST"
  limit_amount = var.budget_limit_usd
  limit_unit   = "USD"
  time_unit    = "MONTHLY"

  cost_filter {
    name   = "TagKeyValue"
    values = ["Project$${var.project_tag_value}"]
  }

  dynamic "notification" {
    for_each = {
      fifty   = 50
      eighty  = 80
      hundred = 100
    }

    content {
      comparison_operator        = "GREATER_THAN"
      threshold                  = notification.value
      threshold_type             = "PERCENTAGE"
      notification_type          = "ACTUAL"
      subscriber_email_addresses = var.budget_alert_emails
    }
  }
}

