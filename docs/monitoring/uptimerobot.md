# UptimeRobot Monitoring

Dokumen ini menyiapkan monitor eksternal sederhana untuk fase 4 CI/CD dan monitoring.

> Updated by OpenCode on `2026-04-17`.

## Endpoint yang Disarankan

1. Public app health via ALB or CloudFront
   - URL: `http://bookcabin-free-tier-alb-1828807141.ap-southeast-1.elb.amazonaws.com/health`
2. API Gateway uptime
   - URL: `https://3r9t2pqnji.execute-api.ap-southeast-1.amazonaws.com/v1/webhook/ota`

## Konfigurasi Monitor

### Monitor 1: App Health

- Type: `HTTP(s)`
- Name: `bookcabin-free-tier-app`
- URL: `http://bookcabin-free-tier-alb-1828807141.ap-southeast-1.elb.amazonaws.com/health`
- Monitoring interval: `5 minutes`
- Timeout: `30 seconds`
- Keyword: optional, `ok`

### Monitor 2: API Gateway Reachability

- Type: `HTTP(s)`
- Name: `bookcabin-free-tier-api-gateway`
- URL: `https://3r9t2pqnji.execute-api.ap-southeast-1.amazonaws.com/v1/webhook/ota`
- Monitoring interval: `5 minutes`
- Method: `POST`
- Request body:

```json
{
  "event_type": "booking.created",
  "provider": "uptimerobot",
  "payload": {
    "ota_booking_id": "UPTIME-CHECK",
    "room_id": 1,
    "guest_name": "Uptime Robot",
    "guest_email": "monitor@example.com",
    "guest_phone": "0000000000",
    "check_in": "2026-05-01T14:00:00+08:00",
    "check_out": "2026-05-01T16:00:00+08:00",
    "total_price": 10000
  }
}
```

Catatan:
- Jika Anda tidak ingin monitor eksternal terus mengirim event ke SQS, pakai monitor GET ke ALB `/health` saja sebagai baseline uptime.
- Monitor API Gateway sebaiknya dipakai hanya jika Anda menerima noise request periodik ke queue.
