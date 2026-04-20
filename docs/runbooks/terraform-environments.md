# Runbook Terraform Environments

Dokumen ini menjelaskan alur aman untuk menjalankan Terraform pada environment `free-tier`, `staging`, dan `production`.

> Updated by OpenCode on `2026-04-17` untuk kebutuhan operasional Antigravity.

## Prinsip Dasar

- Gunakan `infra/bootstrap/backend` hanya sekali untuk membuat backend Terraform.
- Gunakan `terraform.tfvars` sebagai base config non-sensitif.
- Gunakan `terraform.secrets.auto.tfvars` sebagai override lokal untuk nilai sensitif.
- Terraform akan menulis password database ke `SSM Parameter Store SecureString` agar EC2 mengambil secret saat bootstrap runtime.
- Jangan jalankan `terraform apply` tanpa `terraform plan` lebih dulu jika infra sudah aktif di AWS.

## Struktur Secret Lokal

Untuk setiap environment, buat file lokal ini dari file contoh:

- `infra/environments/free-tier/terraform.secrets.auto.tfvars`
- `infra/environments/staging/terraform.secrets.auto.tfvars`
- `infra/environments/production/terraform.secrets.auto.tfvars`

Isi minimal:

```hcl
secret_overrides = {
  allowed_ssh_cidr    = "203.0.113.10/32"
  public_key          = "ssh-rsa CHANGE_ME"
  db_password         = "CHANGE_ME_ROTATE_ME"
  budget_alert_emails = ["your-email@example.com"]
}
```

## Bootstrap Backend

```powershell
cd "D:\Project AWS\Bandara\infra\bootstrap\backend"
terraform init
terraform plan
terraform apply
```

## Free-Tier

```powershell
cd "D:\Project AWS\Bandara\infra\environments\free-tier"
terraform init
terraform plan
terraform apply
terraform output
```

Destroy:

```powershell
terraform plan -destroy
terraform destroy
```

Catatan:

- Infra free-tier Anda sudah berjalan di AWS, jadi setiap plan harus direview dengan hati-hati.
- Jika output `redis_endpoint` kosong, berarti Redis belum aktif atau belum diprovision di state aktif tersebut.
- Output `db_password_parameter_name` menunjukkan lokasi parameter SecureString yang dipakai runtime EC2.

## Staging

```powershell
cd "D:\Project AWS\Bandara\infra\environments\staging"
terraform init
terraform plan
terraform apply
terraform output
```

## Production

```powershell
cd "D:\Project AWS\Bandara\infra\environments\production"
terraform init
terraform plan
terraform apply
terraform output
```

Catatan:

- `production` saat ini masih memakai jalur compute EC2-based karena module ECS/Fargate belum tersedia.
- Jangan anggap `production` sepenuhnya production-ready sebelum path compute production selesai dibuat.

## Troubleshooting Cepat

### State lock tertahan

```powershell
terraform force-unlock LOCK_ID
```

Gunakan hanya jika Anda yakin tidak ada apply lain yang sedang berjalan.

### AMI tidak valid

- Cek region pada config environment.
- Pastikan `ec2_ami_id` valid untuk `ap-southeast-1`.

### Drift pada infra aktif

- Jalankan `terraform plan` dan review output.
- Jangan langsung apply jika plan menunjukkan recreate resource penting.

### Secret salah atau belum diisi

- Pastikan file `terraform.secrets.auto.tfvars` ada di environment yang sedang dipakai.
- Pastikan nilai override sudah valid.

## Guardrail Biaya

- Hindari NAT Gateway pada free-tier.
- Redis aktif pada free-tier saat ini, jadi review biaya tetap wajib dilakukan.
- Review AWS Budget sebelum apply perubahan besar.
- Hentikan resource belajar saat tidak dipakai.
