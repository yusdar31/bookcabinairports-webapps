# Monitoring & Uptime Setup

## UptimeRobot Monitors

Setup berikut di [UptimeRobot](https://uptimerobot.com/):

### 1. Health Check — Laravel App
- **Type:** HTTP(s)
- **URL:** `https://bookcabin.site/api/health`
- **Interval:** 5 menit
- **Alert:** Email + Telegram

### 2. Health Check — Golang Agent
- **Type:** HTTP(s)
- **URL:** `http://<EC2-IP>:9000/health` (internal / via SSH tunnel)
- **Interval:** 5 menit

### 3. SSL Certificate
- **Type:** HTTP(s) - Keyword
- **URL:** `https://bookcabin.site`
- **Keyword:** "Bookcabin"
- **Interval:** 1 jam

## CloudWatch Alarms (AWS Free Tier)

```bash
# CPU > 80% selama 5 menit
aws cloudwatch put-metric-alarm \
  --alarm-name "bookcabin-cpu-high" \
  --metric-name CPUUtilization \
  --namespace AWS/EC2 \
  --statistic Average \
  --period 300 \
  --evaluation-periods 2 \
  --threshold 80 \
  --comparison-operator GreaterThanThreshold \
  --dimensions "Name=InstanceId,Value=<INSTANCE_ID>" \
  --alarm-actions "<SNS_TOPIC_ARN>"

# Disk > 85%
aws cloudwatch put-metric-alarm \
  --alarm-name "bookcabin-disk-high" \
  --metric-name DiskSpaceUtilization \
  --namespace System/Linux \
  --statistic Average \
  --period 300 \
  --evaluation-periods 2 \
  --threshold 85 \
  --comparison-operator GreaterThanThreshold \
  --alarm-actions "<SNS_TOPIC_ARN>"
```

## Log Rotation (EC2)

Tambahkan ke `/etc/logrotate.d/bookcabin`:

```
/opt/bookcabin/app/storage/logs/*.log {
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
    create 0640 www-data www-data
}
```

## Docker Log Limits

Di `docker-compose.prod.yml`, tambahkan:
```yaml
logging:
  driver: "json-file"
  options:
    max-size: "10m"
    max-file: "3"
```

## Manual Health Check Script

```bash
#!/bin/bash
echo "=== Laravel App ==="
curl -sf http://localhost/api/health | jq .

echo "=== Booking Agent ==="
curl -sf http://localhost:9000/health

echo "=== Docker Containers ==="
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

echo "=== Disk Usage ==="
df -h /

echo "=== Memory ==="
free -m
```
