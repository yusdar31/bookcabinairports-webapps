# GitHub Actions CI/CD Secrets Setup

## Secrets yang Diperlukan

Buka **Settings → Secrets and variables → Actions** di repo GitHub, lalu tambahkan:

| Secret | Deskripsi | Contoh |
|:---|:---|:---|
| `EC2_HOST` | IP publik atau domain EC2 | `13.212.xxx.xxx` |
| `EC2_USER` | SSH username | `ec2-user` |
| `EC2_SSH_KEY` | Private key SSH (isi file `.pem`) | `-----BEGIN RSA PRIVATE KEY-----...` |

## Cara Setup

### 1. Generate SSH Key Pair (jika belum ada)

```bash
ssh-keygen -t ed25519 -f bookcabin-deploy -C "github-actions-deploy"
```

### 2. Tambahkan Public Key ke EC2

```bash
ssh -i existing-key.pem ec2-user@<EC2-IP> \
  "echo '$(cat bookcabin-deploy.pub)' >> ~/.ssh/authorized_keys"
```

### 3. Tambahkan Private Key sebagai GitHub Secret

```bash
# Copy isi private key
cat bookcabin-deploy

# Paste ke GitHub: Settings → Secrets → New → EC2_SSH_KEY
```

### 4. Setup EC2 Host & User

```
EC2_HOST = 13.212.xxx.xxx (dari Terraform output)
EC2_USER = ec2-user (Amazon Linux 2)
```

## Workflow Triggers

| Event | Workflow | Action |
|:---|:---|:---|
| Push ke `main` | `deploy.yml` | Test → Build → Deploy |
| PR ke `main` / `develop` | `lint.yml` | PHPUnit + Pint + Go vet |
| Manual | `deploy.yml` (workflow_dispatch) | Force deploy |

## Persiapan EC2

Pastikan EC2 sudah memiliki:
- Docker & Docker Compose terinstall
- Direktori `/opt/bookcabin` sudah ada
- File `.env` tersedia di `/opt/bookcabin/app/.env`
