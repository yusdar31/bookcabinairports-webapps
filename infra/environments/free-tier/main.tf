terraform {
  required_version = ">= 1.6.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

provider "aws" {
  region = local.effective_config.aws_region

  default_tags {
    tags = {
      Project     = local.effective_config.project_tag_value
      Environment = local.effective_config.environment
      ManagedBy   = "terraform"
    }
  }
}

locals {
  effective_config = merge(var.config, var.secret_overrides)
}

module "stack" {
  source = "../../modules/environment-stack"

  config = local.effective_config
}
