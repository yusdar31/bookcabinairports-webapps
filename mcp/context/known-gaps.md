# Known Gaps Snapshot

This file captures the current project gaps that were identified during the latest repository audit.

## High priority

- EC2 runtime secret flow is inconsistent: the infra creates an SSM parameter for the database password, but EC2 user data still writes the database password directly into `.env`.
- GitHub deploy workflow still hardcodes a database password value instead of consuming the runtime secret flow.
- Booking lifecycle marks rooms as `occupied` too early, even while a booking is still `pending`.

## Medium priority

- OTA booking modification in the Go agent updates dates without a fresh conflict check.
- Midtrans finish callback URL is configured, but there is no matching route or page for the payment completion flow.
- Deployment guidance is split between manual SSH/systemd steps and Docker/ECR/GitHub Actions.

## Working assumption

The first MCP version should help agents understand these risks quickly, not automate changes around them yet.
