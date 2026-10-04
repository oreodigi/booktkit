# BookTKIT remote MCP

BookTKIT production is hosted in cPanel. This MCP service is an independent QA control plane; it does not host or replace the BookTKIT application.

## Architecture

ChatGPT Project -> authenticated HTTPS MCP -> GitHub Actions -> Playwright -> BookTKIT target.

The control plane never receives BookTKIT production database or cPanel credentials. GitHub Actions executes the browser/API tests.

## Required runtime secrets

- `BOOKTKIT_GITHUB_TOKEN`: fine-grained GitHub token restricted to `oreodigi/booktkit`, with Actions read/write and repository metadata read.
- `BOOKTKIT_MCP_TOKEN`: independent bearer secret used to protect the public MCP endpoint.

Do not commit either secret.

## Hosting

The MCP can run on any Node-compatible HTTPS host. It is intentionally independent of the cPanel production application. A dedicated BookTKIT subdomain such as `testing.booktkit.com` is appropriate if the chosen host supports Node and HTTPS.

## Production policy

Production targets are read-only. The dispatched workflow hard-codes `BOOKTKIT_ALLOW_MUTATIONS=false` and `BOOKTKIT_RAZORPAY_TEST_ENABLED=false`.

Authenticated state-changing E2E requires a separate non-production BookTKIT environment and a separate workflow.
