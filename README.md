# Bookcabin — Capsule Hotel & Airport Food POS

Dummy project untuk belajar AWS Free Tier berdasarkan **PRD-SHH-FREETIER-2025-001**.
Sistem pemesanan kamar kapsul + POS Food & Beverage di Bandara Sultan Hasanuddin, Makassar.

> **AWS Region:** `ap-southeast-1` (Singapore) | **Estimasi biaya:** rendah, bergantung free tier dan AWS credits | **Stack:** PHP Laravel + Golang + Docker

## Updated by OpenCode

- Tanggal perubahan: `2026-04-17`
- Perubahan oleh: `OpenCode`
- Ringkasan: sinkronisasi dokumentasi dengan infra aktif di AWS, menambah pola secret lokal yang tidak di-commit, menambah runbook Terraform, menambah changelog khusus untuk kebutuhan Antigravity, dan menyiapkan password database runtime lewat SSM Parameter Store standard.

## Struktur Repository

```
/app          — Source code PHP Laravel (booking, POS, admin dashboard)
/agent        — Source code Golang (SQS consumer, booking agent)
/infra        — Terraform IaC (free-tier, staging, production)
/docs         — Dokumentasi teknis (ERD, arsitektur, runbook, changelog)
/postman      — Postman collection untuk testing API & simulasi OTA webhook
/scripts      — Helper: start/stop EC2, backup DB, cek biaya
/.github      — GitHub Actions CI/CD workflows
/docker       — Dockerfile dan compose files
```

## Arsitektur (Ringkas)

```
User → CloudFront → ALB → EC2 (Docker: App + Agent) → RDS MySQL
OTA Webhook → API Gateway → SQS → Golang Agent → RDS MySQL
Cache path → ElastiCache Redis
```

## Quick Start (Urutan Setup)

### 1. Bootstrap Terraform Backend
```bash
cd infra/bootstrap/backend
terraform init && terraform plan && terraform apply
```

### 2. Isi Secret Lokal Terraform
Jangan simpan secret nyata di `terraform.tfvars`.

Copy file contoh secret lokal:

```bash
copy infra\environments\free-tier\terraform.secrets.auto.tfvars.example infra\environments\free-tier\terraform.secrets.auto.tfvars
```

Lalu isi nilai nyata di file `terraform.secrets.auto.tfvars`:
- `allowed_ssh_cidr`
- `public_key`
- `db_password`
- `budget_alert_emails`

Catatan:
- File ini sudah di-ignore oleh Git.
- Password database yang sebelumnya pernah muncul di workspace sebaiknya dianggap terekspos dan dirotasi.
- Password database akan disimpan juga ke `SSM Parameter Store SecureString` untuk kebutuhan runtime EC2.

### 3. Deploy Infrastruktur
```bash
cd infra/environments/free-tier
terraform init && terraform plan && terraform apply
```

### 4. Deploy Aplikasi PHP ke EC2
```bash
# SSH ke EC2, lalu:
git clone <repo> /var/www/bookcabin
cd /var/www/bookcabin/app
composer install
cp .env.example .env && php artisan key:generate
# Edit .env dengan nilai dari terraform output
php artisan migrate --seed
```

### 5. Deploy Golang Agent
```bash
cd agent && make build-linux
scp booking-agent-linux ubuntu@<EC2_IP>:/opt/bookcabin/agent/booking-agent
scp agent/bookcabin-agent.service ubuntu@<EC2_IP>:/etc/systemd/system/
ssh ubuntu@<EC2_IP> "sudo systemctl enable --now bookcabin-agent"
```

## Testing OTA Webhook

Import `postman/ota-webhook-simulation.postman_collection.json` ke Postman, isi variabel `api_gateway_url` dari output Terraform, lalu jalankan request simulasi.

## Dokumentasi

- [Arsitektur Sistem](docs/architecture.md)
- [ERD Database](docs/erd.md)
- [Runbook Terraform Environments](docs/runbooks/terraform-environments.md)
- [GitHub Secrets untuk CI/CD](docs/ci-cd/github-secrets.md)
- [OpenCode Change Log](docs/changes/2026-04-17-opencode-update.md)
- [UptimeRobot Monitoring](docs/monitoring/uptimerobot.md)

## Catatan Penting

- Tidak ada NAT Gateway pada free-tier.
- ALB dipakai di arsitektur saat ini. Biaya tetap harus diawasi karena kelayakan gratis bergantung free tier dan credit akun AWS.
- Redis diaktifkan untuk free-tier saat ini. Tetap monitor biaya karena coverage gratis bergantung free tier dan credit akun AWS.
- Selalu review `terraform plan` sebelum apply karena infra free-tier sudah aktif di AWS.
- **Jangan commit `.env`, credentials, atau file secret lokal Terraform ke Git.**
