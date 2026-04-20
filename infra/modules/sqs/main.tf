resource "aws_sqs_queue" "dlq" {
  name                      = var.dlq_name
  message_retention_seconds = 345600

  tags = merge(var.tags, {
    Name = var.dlq_name
  })
}

resource "aws_sqs_queue" "main" {
  name                       = var.queue_name
  message_retention_seconds  = 345600
  visibility_timeout_seconds = 60

  redrive_policy = jsonencode({
    deadLetterTargetArn = aws_sqs_queue.dlq.arn
    maxReceiveCount     = 3
  })

  tags = merge(var.tags, {
    Name = var.queue_name
  })
}
