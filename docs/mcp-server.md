# Bookcabin MCP Server

Dokumen ini menjelaskan MCP server lokal yang disiapkan untuk repo Bookcabin agar Codex, OpenCode, dan Antigravity memakai konteks proyek yang sama.

## Tujuan

- Mengurangi pengulangan eksplorasi repo setiap kali agent mulai bekerja
- Menyamakan pemahaman agent tentang arsitektur, runbook, dan gap proyek
- Menyediakan jalur integrasi AI yang aman sebelum menambah tool yang bisa menulis data

## Lokasi

Folder MCP ada di [mcp](../mcp/README.md).
Panduan konfigurasi agent ada di [Konfigurasi MCP untuk Codex, OpenCode, dan Antigravity](mcp-agent-config.md).

## Cakupan v0.2

### Resources

- `project://overview`
- `project://architecture`
- `project://runbook/terraform`
- `project://changes/opencode-2026-04-17`
- `project://changes/aws-mcp-2026-04-21`
- `project://agent-playbook`
- `project://known-gaps`

### Tools

- `project_overview`
- `repo_status`
- `search_docs`
- `list_http_endpoints`
- `health_check_services`
- `get_context_document`
- `aws_environment_status`
- `aws_list_ec2_instances`
- `aws_list_ssm_managed_instances`
- `aws_list_ecr_repositories`
- `aws_list_load_balancers`
- `aws_list_cloudwatch_log_groups`
- `aws_list_cloudwatch_log_streams`
- `aws_tail_cloudwatch_logs`
- `aws_list_cloudwatch_metrics`
- `aws_get_cloudwatch_metric_series`
- `aws_list_ssm_parameters`
- `aws_list_cloudfront_distributions`
- `aws_list_rds_instances`
- `aws_list_target_groups`
- `aws_get_target_group_health`
 - `aws_list_ecr_image_tags`
- `aws_run_ssm_diagnostic_command`
- `aws_probe_http_endpoint`
- `aws_diagnose_delivery_path`
- `aws_detect_ec2_container_drift`
- `aws_incident_alb_5xx_snapshot`
- `github_actions_workflow_catalog`
- `github_actions_recent_runs`
- `github_actions_find_run_for_sha`
- `aws_incident_target_unhealthy_snapshot`
- `aws_trace_deployment_source_of_truth`
- `aws_incident_summary`
- `aws_run_ssm_remediation_action`

### Prompts

- `plan_bugfix`
- `plan_feature`
- `plan_uiux_change`

## Kenapa ini cocok untuk 3 coding agent

Dengan MCP ini, ketiga agent tidak perlu menebak-nebak:

- struktur repo
- catatan perubahan AI sebelumnya
- runbook terraform
- daftar route yang aktif
- gap arsitektur yang sudah diketahui

Artinya, agent lebih cepat masuk ke pekerjaan yang benar: fixing bug, tambah fitur, atau edit UI/UX.

## Batasan saat ini

- Belum ada operasi tulis
- Belum ada akses database langsung
- Belum ada integrasi auth ke Laravel session
- Belum ada trigger deploy, migrate, atau Terraform
- AWS masih bergantung pada `aws` CLI lokal dan credential/IAM di environment host
- Tool SSM parameter hanya expose metadata, bukan value secret
- Tool SSM diagnostik dibatasi ke allowlist command observability dan bukan shell bebas
- Probe endpoint HTTP dibatasi ke metode `GET` dan `HEAD`
- Observability GitHub Actions saat ini terbatas ke workflow file lokal bila `gh` CLI belum tersedia
- Drift detection masih berbasis heuristik pencocokan nama image/repository/container
- Lookup run GitHub Actions bisa memakai GitHub API fallback dan karena itu tetap bergantung pada visibility repo serta rate limit/token
- Remediation SSM yang tersedia bersifat sempit, ter-audit, dan hanya untuk restart action tertentu

## Langkah berikutnya yang disarankan

1. Tambahkan tool read-only untuk dashboard bisnis dari API lokal jika environment dev aktif.
2. Tambahkan tool validasi deploy yang membaca file compose, workflow, dan health endpoint sekaligus.
3. Tambahkan mode HTTP transport jika nanti MCP ingin dipakai lintas mesin, bukan hanya stdio lokal.
4. Tambahkan write tools yang sangat sempit dan auditable bila memang dibutuhkan, misalnya membuat booking draft test atau membuat issue checklist internal.
