# Panduan Setup AWS Credentials untuk Terraform

## Langkah 1 — Buat IAM User di AWS Console

### 1.1 Login ke AWS Console
Buka: https://console.aws.amazon.com

> ⚠️ Gunakan IAM user, BUKAN root account untuk daily development

### 1.2 Buat IAM User
1. Buka **IAM** → **Users** → **Create user**
2. Username: `bookcabin-terraform-dev`
3. **Access type**: ✅ Programmatic access (Access key)
4. **Permissions**: Attach policies directly, pilih:
   - `AdministratorAccess` *(untuk dummy project — di production gunakan policy yang lebih terbatas)*
5. Klik **Create user**
6. **Download** file `credentials.csv` atau **copy** nilai:
   - `Access key ID`
   - `Secret access key`

> ⚠️ Secret access key hanya muncul SEKALI. Simpan baik-baik!

---

## Langkah 2 — Konfigurasi AWS CLI

Jalankan di terminal:
```powershell
aws configure
```

Isi interaktif:
```
AWS Access Key ID [None]: PASTE_ACCESS_KEY_ID
AWS Secret Access Key [None]: PASTE_SECRET_ACCESS_KEY
Default region name [None]: ap-southeast-1
Default output format [None]: json
```

### Verifikasi berhasil:
```powershell
aws sts get-caller-identity
```
Output yang diharapkan:
```json
{
    "UserId": "AIDAXXXXXXXXXXXXXXXXX",
    "Account": "123456789012",
    "Arn": "arn:aws:iam::123456789012:user/bookcabin-terraform-dev"
}
```

---

## Langkah 3 — Cari AMI Ubuntu 22.04 untuk ap-southeast-1

Setelah `aws configure` berhasil, jalankan:
```powershell
aws ec2 describe-images `
  --region ap-southeast-1 `
  --owners 099720109477 `
  --filters "Name=name,Values=ubuntu/images/hvm-ssd/ubuntu-jammy-22.04-amd64-server-*" `
            "Name=state,Values=available" `
  --query "sort_by(Images, &CreationDate)[-1].{ID:ImageId,Name:Name,Created:CreationDate}" `
  --output table
```
Copy nilai `ImageId` yang muncul → isi ke `ec2_ami_id` di `terraform.tfvars`.

---

## Langkah 4 — Generate SSH Key (jika belum ada)

```powershell
# Cek apakah sudah ada
Test-Path "$env:USERPROFILE\.ssh\id_rsa.pub"

# Jika belum ada, generate:
ssh-keygen -t rsa -b 4096 -C "bookcabin-ec2" -f "$env:USERPROFILE\.ssh\id_rsa"

# Lihat public key untuk dimasukkan ke terraform.tfvars
Get-Content "$env:USERPROFILE\.ssh\id_rsa.pub"
```

---

## Langkah 5 — Isi Secret Lokal Terraform

Jangan masukkan nilai sensitif langsung ke `terraform.tfvars`.

Copy file contoh berikut:

```powershell
Copy-Item "D:\Project AWS\Bandara\infra\environments\free-tier\terraform.secrets.auto.tfvars.example" "D:\Project AWS\Bandara\infra\environments\free-tier\terraform.secrets.auto.tfvars"
```

Lalu isi file `infra/environments/free-tier/terraform.secrets.auto.tfvars`:

```hcl
secret_overrides = {
  allowed_ssh_cidr    = "xxx.xxx.xxx.xxx/32"
  public_key          = "ssh-rsa AAAA..."
  db_password         = "BuatPasswordKuat123!"
  budget_alert_emails = ["emailmu@gmail.com"]
}
```

`terraform.tfvars` tetap dipakai sebagai base config non-sensitif.

---

## Langkah 6 — Jalankan Bootstrap Terraform

```powershell
cd "d:\Project AWS\Bandara\infra\bootstrap\backend"
terraform init
terraform apply
```

Ketik `yes` saat diminta konfirmasi.

**Output yang diharapkan:** S3 bucket `bookcabin-terraform-state-ap-southeast-1` dan DynamoDB table `bookcabin-terraform-locks` dibuat.

---

## Langkah 7 — Deploy Free-Tier Environment

```powershell
cd "d:\Project AWS\Bandara\infra\environments\free-tier"
terraform init
terraform plan    # review dulu sebelum apply!
terraform apply
```

**Output yang akan tersedia setelah apply:**
- EC2 public IP (untuk SSH dan akses web)
- RDS endpoint (untuk koneksi database)
- S3 bucket name
- SQS queue URL
- API Gateway URL

---

## Tips Keamanan

- Jangan commit `terraform.secrets.auto.tfvars` ke Git (sudah ada di `.gitignore`)
- Aktifkan MFA di akun AWS root
- Set billing alert di $1 dan $5 di AWS Billing console
- Setelah selesai belajar: stop EC2 dan RDS untuk hemat Free Tier hours
- Jika password database pernah tersimpan di workspace atau log, anggap terekspos dan lakukan rotasi

