# Bookcabin Agent Playbook

This project MCP is intended to be shared by Codex, OpenCode, and Antigravity.

## Primary goals

- Give every coding agent the same high-signal project context.
- Reduce repeated repo exploration before small fixes or UI changes.
- Keep access safe by default: read-only first, narrow write actions later.

## Recommended workflow

1. Read `project://overview` before proposing changes.
2. Read `project://known-gaps` before touching infra, booking flow, or deployment.
3. Use `search_docs` to find architecture, runbooks, or changelog context.
4. Use `list_http_endpoints` before editing controllers or frontend API calls.
5. Use `health_check_services` only when a local stack is expected to be running.

## Guardrails

- Do not expose `.env`, Terraform secrets, or raw SSM values through MCP.
- Treat deploy config and runtime secrets as sensitive even in local development.
- Keep destructive actions out of the first MCP version.
- Prefer tools that summarize or validate context over tools that mutate data.

## Good use cases

- Error triage
- Feature scoping
- UI and UX changes that need route and layout awareness
- Deployment sanity checks
- Finding previous AI notes and operational runbooks

## Not in scope yet

- Direct database writes
- Terraform apply or infrastructure mutation
- Payment actions
- User impersonation or auth bypass
