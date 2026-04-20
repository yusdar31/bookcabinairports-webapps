#!/bin/bash
set -euo pipefail

PROJECT_TAG="${PROJECT_TAG:-bookcabin}"
RDS_IDENTIFIER="${RDS_IDENTIFIER:-bookcabin-free-tier-db}"

echo "Starting EC2..."
aws ec2 start-instances \
  --instance-ids "$(aws ec2 describe-instances \
    --filters Name=tag:Project,Values=${PROJECT_TAG} Name=instance-state-name,Values=stopped \
    --query 'Reservations[].Instances[].InstanceId' \
    --output text)"

echo "Starting RDS..."
aws rds start-db-instance \
  --db-instance-identifier "${RDS_IDENTIFIER}"

echo "Resources started."
