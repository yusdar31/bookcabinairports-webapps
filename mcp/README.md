# Bookcabin MCP

Shared Model Context Protocol server for this repository.

It is designed so Codex, OpenCode, and Antigravity can use the same high-signal project context before making code changes.

## What it exposes

- Static project resources:
  - repo overview
  - architecture summary
  - Terraform runbook
  - OpenCode change log
  - shared agent playbook
  - current known gaps
- Read-only tools:
  - `project_overview`
  - `repo_status`
  - `search_docs`
  - `list_http_endpoints`
  - `health_check_services`
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
- Reusable prompts:
  - `plan_bugfix`
  - `plan_feature`
  - `plan_uiux_change`

## Install

```bash
cd mcp
npm install
npm run build
```

## Run

```bash
cd mcp
npm start
```

For local development:

```bash
cd mcp
npm run dev
```

## Environment

Optional environment variables:

- `BOOKCABIN_PROJECT_ROOT`
  - Absolute path to repo root. Defaults to the parent of the `mcp` folder.
- `BOOKCABIN_APP_HEALTH_URL`
  - Defaults to `http://127.0.0.1:8080/api/health`
- `BOOKCABIN_AGENT_HEALTH_URL`
  - Defaults to `http://127.0.0.1:9000/health`
- `AWS_REGION` or `AWS_DEFAULT_REGION`
  - Defaults to `ap-southeast-1`
- `AWS_PROFILE`
  - Optional named AWS CLI profile used by the read-only AWS tools

## AWS capability notes

- AWS access is implemented via local `aws` CLI, so credentials and IAM policy stay outside the repo.
- Current AWS tools are intentionally read-only.
- `aws_list_ssm_parameters` returns metadata only and never reads parameter values.
- `aws_run_ssm_diagnostic_command` is restricted to a fixed allowlist of observability commands for EC2/Docker diagnostics.
- `aws_probe_http_endpoint` only supports `GET` and `HEAD`.
- `github_actions_workflow_catalog` reads local workflow files when GitHub CLI is unavailable on the host.
- `aws_detect_ec2_container_drift` uses name/tag heuristics and should be treated as an operational hint, not a deployment source of truth.
- `github_actions_recent_runs` can fall back to GitHub API using `GITHUB_TOKEN` or unauthenticated requests if the repository is public.
- `aws_run_ssm_remediation_action` is intentionally write-capable but limited to a tiny audited allowlist of restart actions.

## Suggested client registration

Register this MCP server in each coding agent as a stdio server that runs:

```bash
node /absolute/path/to/Bookcabin/mcp/dist/index.js
```

Point all three agents at the same command so they share one context contract.
