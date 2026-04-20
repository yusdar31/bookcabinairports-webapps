resource "aws_ecr_repository" "app" {
  name                 = var.app_repository_name
  image_tag_mutability = var.image_tag_mutability

  image_scanning_configuration {
    scan_on_push = true
  }

  tags = merge(var.tags, {
    Name = "${var.name_prefix}-app-ecr"
  })
}

resource "aws_ecr_repository" "api" {
  name                 = var.api_repository_name
  image_tag_mutability = var.image_tag_mutability

  image_scanning_configuration {
    scan_on_push = true
  }

  tags = merge(var.tags, {
    Name = "${var.name_prefix}-api-ecr"
  })
}
