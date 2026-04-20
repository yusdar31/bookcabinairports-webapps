#!/bin/bash
set -euo pipefail

PROJECT_TAG="${PROJECT_TAG:-bookcabin}"
RDS_IDENTIFIER="${RDS_IDENTIFIER:-bookcabin-free-tier-db}"

echo "Stopping EC2..."
aws ec2 stop-instances \
  --instance-ids "$(aws ec2 describe-instances \
    --filters Name=tag:Project,Values=${PROJECT_TAG} Name=instance-state-name,Values=running \
    --query 'Reservations[].Instances[].InstanceId' \
    --output text)"

echo "Stopping RDS..."
aws rds stop-db-instance \
  --db-instance-identifier "${RDS_IDENTIFIER}"

echo "Resources stopped. RDS auto-starts after 7 days."
