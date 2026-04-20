# GitHub Secrets untuk CI/CD

Dokumen ini menjelaskan secret GitHub yang dibutuhkan oleh workflow CI/CD di repository ini.

> Updated by OpenCode on `2026-04-17`.

## Cara Membuka GitHub Secrets

Di repository GitHub Anda:

1. Buka `Settings`
2. Buka `Secrets and variables`
3. Pilih `Actions`
4. Klik `New repository secret`

## Secrets yang Dibutuhkan

### 1. `AWS_ACCESS_KEY_ID`

- Wajib: `Ya`
- Digunakan untuk: login AWS di GitHub Actions
- Nilai: Access key dari IAM user/deploy user AWS

### 2. `AWS_SECRET_ACCESS_KEY`

- Wajib: `Ya`
- Digunakan untuk: autentikasi AWS di GitHub Actions
- Nilai: Secret access key pasangan dari `AWS_ACCESS_KEY_ID`

### 3. `ECR_REPO_APP`

- Wajib: `Ya`
- Digunakan untuk: tag dan push image aplikasi
- Format:

```text
479149268499.dkr.ecr.ap-southeast-1.amazonaws.com/bookcabin-app
```

### 4. `ECR_REPO_API`

- Wajib: `Ya`
- Digunakan untuk: tag dan push image agent
- Format:

```text
479149268499.dkr.ecr.ap-southeast-1.amazonaws.com/bookcabin-api
```

### 5. `EC2_HOST`

- Wajib: `Ya`
- Digunakan untuk: SSH deploy ke EC2
- Nilai: public IP atau DNS EC2 target
- Contoh:

```text
18.140.17.117
```

### 6. `EC2_USER`

- Wajib: `Ya`
- Digunakan untuk: username SSH pada deploy workflow
- Nilai harus sesuai dengan AMI yang dipakai instance Anda

Untuk kondisi Anda saat ini:

```text
ubuntu
```

Catatan:
- Jangan isi `ec2-user` jika instance aktif Anda memakai Ubuntu.

### 7. `EC2_SSH_KEY`

- Wajib: `Ya`
- Digunakan untuk: SSH dari GitHub Actions ke EC2
- Nilai: private key dari pasangan public key yang sudah didaftarkan ke instance

Format isi secret:

```text
-----BEGIN OPENSSH PRIVATE KEY-----
...
-----END OPENSSH PRIVATE KEY-----
```

## Ringkasan Cepat

Secrets minimum untuk workflow `deploy-free-tier.yml`:

- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `ECR_REPO_APP`
- `ECR_REPO_API`
- `EC2_HOST`
- `EC2_USER`
- `EC2_SSH_KEY`

## Dari Mana Mengambil Nilainya

### AWS credentials

Ambil dari IAM user/deploy user yang punya izin minimal untuk:

- ECR login, push, pull
- optional AWS API lain yang dipakai workflow

### ECR repository URL

Ambil dari:

- AWS Console -> ECR -> repository `bookcabin-app`
- AWS Console -> ECR -> repository `bookcabin-api`

atau dari Terraform output dan naming repository yang sudah dipakai sekarang.

### EC2 host

Ambil dari:

- Terraform output `ec2_public_ip`
- atau AWS Console -> EC2 -> Public IPv4 address

### EC2 user

Tentukan dari OS image:

- Ubuntu: `ubuntu`
- Amazon Linux: `ec2-user`

Saat ini instance aktif Anda terdeteksi sebagai Ubuntu, jadi gunakan `ubuntu`.

### SSH private key

Ambil dari file private key lokal yang merupakan pasangan dari public key yang sudah di-register ke EC2.

## Rekomendasi Keamanan

1. Buat IAM user khusus deploy, jangan pakai credential personal harian.
2. Gunakan policy minimum yang diperlukan, jangan `AdministratorAccess` jika bisa dihindari.
3. Rotasi secret secara berkala.
4. Jika nanti pipeline sudah matang, pertimbangkan migrasi ke GitHub OIDC agar tidak menyimpan AWS key statis di GitHub Secrets.

## Catatan untuk Kondisi Repo Ini

- Workflow free-tier sekarang memakai region `ap-southeast-1`
- Workflow deploy via SSH sekarang mengasumsikan user rumah instance ada di `/home/${EC2_USER}`
- Karena instance aktif Anda Ubuntu, secret `EC2_USER` harus bernilai `ubuntu`
