# Arsitektur AWS Free Tier - Bookcabin

Dokumen ini menjelaskan rancangan arsitektur AWS dari repositori ini yang dibangun menggunakan Terraform. Arsitektur ini dirancang modular, aman antara public/private, dan berorientasi biaya rendah dengan memanfaatkan free tier atau AWS credits jika masih tersedia.

> Updated by OpenCode on `2026-04-17` untuk sinkronisasi dengan kondisi infra aktif di AWS dan kebutuhan Antigravity.

## Diagram Arsitektur

```mermaid
architecture-beta
    group aws(cloud)[AWS Cloud - ap-southeast-1]

    group vpc(cloud)[VPC] in aws

    group pub(cloud)[Public Subnets] in vpc
    service igw(internet)[Internet Gateway] in pub
    service alb(server)[Application Load Balancer] in pub
    service ec2(server)[EC2 t3.micro - Docker] in pub

    group priv(cloud)[Private Subnets] in vpc
    service rds(database)[RDS db.t3.micro - MySQL] in priv
    service redis(database)[ElastiCache Redis - Optional] in priv

    service cf(server)[CloudFront CDN] in aws
    service sqs(server)[SQS Queue Webhook] in aws
    service apigw(server)[API Gateway] in aws
    service cw(server)[CloudWatch Dashboard] in aws

    igw:T --> B:alb
    cf:B --> T:alb
    alb:B --> T:ec2
    ec2:B --> T:rds
    ec2:R --> L:redis

    apigw:R --> L:sqs
    sqs:L --> R:ec2
```

## Rincian Komponen Infrastruktur

### 1. Networking
- 1 VPC terisolasi
- 2 public subnets untuk ALB dan EC2
- 2 private subnets untuk RDS dan optional Redis
- Security group memisahkan akses ALB, EC2, RDS, dan Redis

### 2. Edge dan Routing
- **CloudFront** berada di depan origin aplikasi
- **ALB** meneruskan trafik HTTP ke target EC2

### 3. Compute
- **EC2 `t3.micro`** menjadi host container aplikasi dan agent
- **ECR** menyimpan image Docker aplikasi dan agent

### 4. Database dan Cache
- **RDS MySQL `db.t3.micro`** menjadi source of truth untuk free-tier
- **ElastiCache Redis** didukung Terraform sebagai jalur cache opsional

Catatan:
- Redis tidak boleh otomatis diasumsikan gratis selamanya
- Redis hanya layak dipakai bila coverage free tier atau AWS credits akun Anda masih aman
- Jika output `redis_endpoint` kosong pada environment aktif, berarti Redis belum aktif pada deployment itu

### 5. Async Queue
- **API Gateway** menerima webhook publik
- **SQS** menjadi buffer async
- **Golang Agent** di EC2 memproses message dari queue dan DLQ

### 6. Observability dan Biaya
- **AWS Budgets** untuk alert budget
- **CloudWatch Dashboard** untuk visibility dasar

## Status Dokumen

`v1.1 (Synced with Active AWS Infra)`

Tag: `[free-tier, ap-southeast-1, opencode-updated]`
