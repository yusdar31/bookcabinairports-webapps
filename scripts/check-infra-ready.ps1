$ErrorActionPreference = "Stop"
$Region = "ap-southeast-1"
$TfVarsPath = "infra\environments\free-tier\terraform.tfvars"

Write-Host ""
Write-Host "========================================"
Write-Host " Bookcabin - Pre-flight Infra Check"
Write-Host "========================================"
Write-Host ""

$allPassed = $true

# 1. Cek AWS CLI
Write-Host "[1] Mengecek AWS CLI..."
$awsVer = aws --version 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "    [OK] $awsVer"
} else {
    Write-Host "    [FAIL] AWS CLI tidak ditemukan. Install dari: https://aws.amazon.com/cli/"
    $allPassed = $false
}

# 2. Cek AWS Credentials
Write-Host ""
Write-Host "[2] Mengecek AWS credentials..."
$identityJson = aws sts get-caller-identity --output json 2>&1
if ($LASTEXITCODE -eq 0) {
    $identity = $identityJson | ConvertFrom-Json
    Write-Host "    [OK] Account : $($identity.Account)"
    Write-Host "    [OK] ARN     : $($identity.Arn)"
} else {
    Write-Host "    [FAIL] Credentials belum dikonfigurasi!"
    Write-Host "    -> Jalankan: aws configure"
    Write-Host "       Region: ap-southeast-1"
    $allPassed = $false
}

# 3. Cek Region
Write-Host ""
Write-Host "[3] Mengecek region..."
$configuredRegion = aws configure get region 2>&1
if ($configuredRegion -eq $Region) {
    Write-Host "    [OK] Region: $configuredRegion"
} else {
    Write-Host "    [WARN] Region saat ini: '$configuredRegion' (harusnya: $Region)"
    Write-Host "    -> Jalankan: aws configure set region $Region"
}

# 4. Cari AMI Ubuntu 22.04 terbaru
Write-Host ""
Write-Host "[4] Mencari AMI Ubuntu 22.04 terbaru di $Region..."
$amiJson = aws ec2 describe-images --region $Region --owners 099720109477 --filters "Name=name,Values=ubuntu/images/hvm-ssd/ubuntu-jammy-22.04-amd64-server-*" "Name=state,Values=available" --query "sort_by(Images, &CreationDate)[-1].{ID:ImageId,Name:Name,Date:CreationDate}" --output json 2>&1
if ($LASTEXITCODE -eq 0) {
    $ami = $amiJson | ConvertFrom-Json
    Write-Host "    [OK] AMI ID  : $($ami.ID)"
    Write-Host "    [OK] Name    : $($ami.Name)"
    Write-Host "    [OK] Created : $($ami.Date)"
    Write-Host ""
    Write-Host "    -> Salin ke terraform.tfvars:"
    Write-Host "       ec2_ami_id = `"$($ami.ID)`""
} else {
    Write-Host "    [WARN] Tidak bisa query AMI (credentials mungkin belum dikonfigurasi)"
}

# 5. Cek SSH Key
Write-Host ""
Write-Host "[5] Mengecek SSH key..."
$sshPub = "$env:USERPROFILE\.ssh\id_rsa.pub"
if (Test-Path $sshPub) {
    $key = Get-Content $sshPub
    Write-Host "    [OK] SSH key ditemukan: $sshPub"
    Write-Host ""
    Write-Host "    -> Salin ke terraform.tfvars (field public_key):"
    Write-Host "       $key"
} else {
    Write-Host "    [WARN] SSH key belum ada. Generate dengan:"
    Write-Host "    ssh-keygen -t rsa -b 4096 -C bookcabin-ec2 -f `"$env:USERPROFILE\.ssh\id_rsa`""
}

# 6. Cek placeholder di terraform.tfvars
Write-Host ""
Write-Host "[6] Mengecek placeholder di $TfVarsPath..."
if (Test-Path $TfVarsPath) {
    $content = Get-Content $TfVarsPath -Raw
    $issues = @()
    if ($content -match 'ec2_ami_id\s*=\s*""') { $issues += "ec2_ami_id masih kosong" }
    if ($content -match 'CHANGE_ME')            { $issues += "ada nilai CHANGE_ME" }
    if ($content -match 'alerts@example\.com')  { $issues += "budget_alert_emails masih contoh" }
    if ($content -match '0\.0\.0\.0/0')         { $issues += "allowed_ssh_cidr terlalu lebar (ganti ke IP kamu)" }

    if ($issues.Count -eq 0) {
        Write-Host "    [OK] terraform.tfvars siap untuk apply!"
    } else {
        Write-Host "    [WARN] Masih ada yang perlu diisi:"
        foreach ($i in $issues) { Write-Host "       - $i" }
        Write-Host "    -> Edit: $TfVarsPath"
        $allPassed = $false
    }
} else {
    Write-Host "    [FAIL] File terraform.tfvars tidak ditemukan!"
    $allPassed = $false
}

# 7. Cek Terraform
Write-Host ""
Write-Host "[7] Mengecek Terraform..."
$tfVer = terraform --version 2>&1 | Select-Object -First 1
if ($LASTEXITCODE -eq 0) {
    Write-Host "    [OK] $tfVer"
} else {
    Write-Host "    [FAIL] Terraform tidak ditemukan. Install: https://www.terraform.io/downloads"
    $allPassed = $false
}

# Summary
Write-Host ""
Write-Host "========================================"
if ($allPassed) {
    Write-Host " [SIAP] Semua check passed!"
    Write-Host ""
    Write-Host " Urutan selanjutnya:"
    Write-Host "   1. cd infra\bootstrap\backend"
    Write-Host "      terraform init"
    Write-Host "      terraform apply"
    Write-Host ""
    Write-Host "   2. cd infra\environments\free-tier"
    Write-Host "      terraform init"
    Write-Host "      terraform plan"
    Write-Host "      terraform apply"
} else {
    Write-Host " [BELUM] Ada item yang perlu diselesaikan dulu (lihat di atas)"
}
Write-Host "========================================"
Write-Host ""

