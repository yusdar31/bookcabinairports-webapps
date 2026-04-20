# OpenCode Change Log — 2026-04-17

Dokumen ini mencatat perubahan yang dilakukan oleh OpenCode untuk kebutuhan pelacakan perubahan di Antigravity.

## Metadata

- Perubahan oleh: `OpenCode`
- Tanggal: `2026-04-17`
- Konteks: sinkronisasi repository dengan kondisi infra yang sudah aktif di AWS

## Ringkasan Perubahan

1. Menambahkan pola secret lokal yang tidak di-commit menggunakan `secret_overrides`.
2. Membersihkan `terraform.tfvars` dari nilai sensitif yang sebelumnya tersimpan di workspace.
3. Menambahkan file contoh `terraform.secrets.auto.tfvars.example` untuk semua environment.
4. Menyinkronkan README dengan region final `ap-southeast-1 (Singapore)` dan arsitektur aktif saat ini.
5. Menambahkan runbook Terraform per environment.
6. Menambahkan rule `.gitignore` untuk file secret lokal dan artefak debug Terraform.
7. Mengaktifkan Redis pada konfigurasi free-tier.
8. Menambahkan `SSM Parameter Store SecureString` untuk password database runtime agar tidak lagi ditulis permanen ke template `.env` dari source repo.
9. Merapikan artefak fase 4: workflow deploy SSH ke EC2, CI PHPUnit, Postman OTA collection, dokumentasi UptimeRobot, dan load test k6.
10. Menambahkan panduan `GitHub Secrets` untuk membantu konfigurasi Actions deploy.

## File yang Diubah

- `.gitignore`
- `README.md`
- `infra/environments/free-tier/main.tf`
- `infra/environments/free-tier/variables.tf`
- `infra/environments/free-tier/terraform.tfvars`
- `infra/environments/free-tier/outputs.tf`
- `infra/environments/staging/main.tf`
- `infra/environments/staging/variables.tf`
- `infra/environments/staging/terraform.tfvars`
- `infra/environments/production/main.tf`
- `infra/environments/production/variables.tf`
- `infra/environments/production/terraform.tfvars`
- `infra/environments/free-tier/terraform.secrets.auto.tfvars.example`
- `infra/environments/staging/terraform.secrets.auto.tfvars.example`
- `infra/environments/production/terraform.secrets.auto.tfvars.example`
- `docs/architecture.md`
- `docs/runbooks/terraform-environments.md`
- `.github/workflows/deploy-free-tier.yml`
- `.github/workflows/ci-test.yml`
- `postman/ota-webhook-simulation.postman_collection.json`
- `docs/monitoring/uptimerobot.md`
- `docs/ci-cd/github-secrets.md`
- `scripts/k6-load-test.js`
- `infra/modules/ssm-parameters/main.tf`
- `infra/modules/ssm-parameters/variables.tf`
- `infra/modules/ssm-parameters/outputs.tf`
- `infra/modules/ec2/main.tf`
- `infra/modules/ec2/variables.tf`
- `infra/modules/environment-stack/main.tf`
- `infra/modules/environment-stack/outputs.tf`

## Catatan Operasional

- Infra free-tier sudah aktif di AWS saat perubahan ini dibuat.
- Karena itu, perubahan difokuskan dulu pada dokumentasi, keamanan secret lokal, dan guardrail operasional.
- Nilai password database yang pernah tersimpan di workspace sebaiknya dianggap terekspos dan dirotasi.

## Catatan Arsitektur

- Region final: `ap-southeast-1`
- ALB digunakan dalam arsitektur aktif
- Redis didukung Terraform dan diaktifkan pada konfigurasi free-tier saat ini. Biaya tetap harus dipantau sesuai coverage credit/free tier akun AWS.
- AWS Secrets Manager tidak dipakai karena berbiaya per secret. Untuk menjaga biaya tetap rendah, runtime secret dipindahkan ke SSM Parameter Store standard dengan `SecureString`.
