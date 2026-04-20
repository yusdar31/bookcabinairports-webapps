data "aws_ssm_parameter" "al2023" {
  count = var.enabled && var.ami_id == "" ? 1 : 0
  name  = "/aws/service/ami-amazon-linux-latest/al2023-ami-kernel-default-x86_64"
}

locals {
  effective_ami = var.ami_id != "" ? var.ami_id : data.aws_ssm_parameter.al2023[0].value

  user_data = <<-EOT
    #!/bin/bash
    set -euxo pipefail

    if command -v dnf >/dev/null 2>&1; then
      dnf update -y
      dnf install -y docker curl awscli
    elif command -v apt-get >/dev/null 2>&1; then
      export DEBIAN_FRONTEND=noninteractive
      apt-get update -y
      apt-get install -y docker.io curl awscli
    else
      echo "Unsupported package manager" >&2
      exit 1
    fi

    systemctl enable docker
    systemctl start docker
    usermod -aG docker ec2-user

    mkdir -p /usr/local/lib/docker/cli-plugins
    curl -SL https://github.com/docker/compose/releases/latest/download/docker-compose-linux-x86_64 -o /usr/local/lib/docker/cli-plugins/docker-compose
    chmod +x /usr/local/lib/docker/cli-plugins/docker-compose

    DB_PASSWORD=$(aws ssm get-parameter --name ${var.db_password_parameter_name} --with-decryption --region ${var.aws_region} --query Parameter.Value --output text)

    fallocate -l 1G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab

    cat > /home/ec2-user/.env <<'ENVFILE'
    DB_HOST=${var.db_host}
    DB_PORT=3306
    DB_DATABASE=${var.db_name}
    DB_USERNAME=${var.db_username}
    DB_PASSWORD=$DB_PASSWORD
    REDIS_HOST=${var.redis_host}
    REDIS_PORT=6379
    SQS_QUEUE_URL=${var.sqs_queue_url}
    AWS_REGION=${var.aws_region}
    APP_IMAGE=${var.app_image}
    API_IMAGE=${var.api_image}
    ENVFILE

    cat > /home/ec2-user/docker-compose.prod.yml <<'COMPOSE'
    services:
      app:
        image: ${var.app_image}
        container_name: bookcabin-app
        env_file:
          - /home/ec2-user/.env
        ports:
          - "80:80"
        environment:
          APP_ENV: free-tier
          BOOKING_AGENT_URL: http://booking-agent:9000
        restart: unless-stopped
      booking-agent:
        image: ${var.api_image}
        container_name: bookcabin-booking-agent
        env_file:
          - /home/ec2-user/.env
        ports:
          - "9000:9000"
        environment:
          APP_ENV: free-tier
        restart: unless-stopped
    COMPOSE

    aws ecr get-login-password --region ${var.aws_region} | docker login --username AWS --password-stdin ${var.ecr_registry}
    docker pull ${var.app_image}
    docker pull ${var.api_image}
    docker compose -f /home/ec2-user/docker-compose.prod.yml up -d
  EOT
}

resource "aws_key_pair" "this" {
  count      = var.enabled ? 1 : 0
  key_name   = "${var.name_prefix}-key"
  public_key = var.public_key

  tags = merge(var.tags, {
    Name = "${var.name_prefix}-key"
  })
}

resource "aws_iam_role" "this" {
  count = var.enabled ? 1 : 0
  name  = "${var.name_prefix}-ec2-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Principal = {
        Service = "ec2.amazonaws.com"
      }
      Action = "sts:AssumeRole"
    }]
  })

  tags = var.tags
}

resource "aws_iam_role_policy_attachment" "ssm" {
  count      = var.enabled ? 1 : 0
  role       = aws_iam_role.this[0].name
  policy_arn = "arn:aws:iam::aws:policy/AmazonSSMManagedInstanceCore"
}

resource "aws_iam_role_policy_attachment" "ecr_read" {
  count      = var.enabled ? 1 : 0
  role       = aws_iam_role.this[0].name
  policy_arn = "arn:aws:iam::aws:policy/AmazonEC2ContainerRegistryReadOnly"
}

resource "aws_iam_role_policy" "ssm_parameter_read" {
  count = var.enabled ? 1 : 0
  name  = "${var.name_prefix}-ssm-parameter-read"
  role  = aws_iam_role.this[0].id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "ssm:GetParameter",
        ]
        Resource = "arn:aws:ssm:${var.aws_region}:*:parameter${var.db_password_parameter_name}"
      }
    ]
  })
}

resource "aws_iam_instance_profile" "this" {
  count = var.enabled ? 1 : 0
  name  = "${var.name_prefix}-ec2-profile"
  role  = aws_iam_role.this[0].name
}

resource "aws_instance" "this" {
  count                       = var.enabled ? 1 : 0
  ami                         = local.effective_ami
  instance_type               = var.instance_type
  subnet_id                   = var.subnet_id
  vpc_security_group_ids      = [var.security_group_id]
  associate_public_ip_address = true
  key_name                    = aws_key_pair.this[0].key_name
  iam_instance_profile        = aws_iam_instance_profile.this[0].name
  user_data                   = local.user_data

  root_block_device {
    volume_size = var.root_volume_size
    volume_type = "gp2"
  }

  tags = merge(var.tags, {
    Name = "${var.name_prefix}-ec2"
  })
}

resource "aws_eip" "this" {
  count    = var.enabled ? 1 : 0
  domain   = "vpc"
  instance = aws_instance.this[0].id

  tags = merge(var.tags, {
    Name = "${var.name_prefix}-eip"
  })
}

resource "aws_lb_target_group_attachment" "this" {
  count            = var.enabled ? 1 : 0
  target_group_arn = var.target_group_arn
  target_id        = aws_instance.this[0].id
  port             = 80
}
