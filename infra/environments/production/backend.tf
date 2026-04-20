terraform {
  required_version = ">= 1.6.0"

  backend "s3" {
    bucket         = "bookcabin-terraform-state-ap-southeast-1"
    key            = "production/terraform.tfstate"
    region         = "ap-southeast-1"
    dynamodb_table = "bookcabin-terraform-locks"
    encrypt        = true
  }
}

