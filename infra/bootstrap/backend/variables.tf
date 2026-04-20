variable "aws_region" {
  description = "AWS region for backend resources"
  type        = string
  default     = "ap-southeast-1"
}

variable "project_name" {
  description = "Project tag value"
  type        = string
  default     = "bookcabin"
}

variable "state_bucket_name" {
  description = "S3 bucket used for Terraform state"
  type        = string
  default     = "bookcabin-terraform-state-ap-southeast-1"
}

variable "lock_table_name" {
  description = "DynamoDB table used for Terraform state locking"
  type        = string
  default     = "bookcabin-terraform-locks"
}

