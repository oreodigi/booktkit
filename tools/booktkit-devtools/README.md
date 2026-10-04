# BookTKIT DevTools

Repository-level QA and ChatGPT/MCP testing layer for BookTKIT.

## Phase 1
- Playwright desktop/mobile smoke testing.
- Failure screenshots, traces, videos and HTML reports.
- MCP tools: `booktkit_health_check` and `booktkit_run_tests`.
- Production-safe default: public/read-only flows only.

## Local setup
Requires Node.js 20+.

```bash
cd tools/booktkit-devtools
npm ci
npx playwright install chromium webkit
npm run test:smoke
npm run mcp
```

Set `BOOKTKIT_BASE_URL` to staging when tests may mutate data. Never commit credentials.

## MCP client
Run `npm run mcp` for local stdio tools or `npm run mcp:http` for the protected HTTPS control plane. The remote tools dispatch approved GitHub Actions suites on `main` and list their results; see `REMOTE-MCP.md` for secrets and hosting.

## Verification
CI runs the complete read-only suite across desktop Chromium, mobile Chromium and mobile WebKit. Both browser engines must be installed. Dependencies are pinned by `package-lock.json` and installed with `npm ci`.

API probes request JSON and verify real HTTP rejection statuses rather than accepting redirected login pages. Maps regression coverage forces immediate callback execution to catch loading-order races. Runtime checks wait for page load so deferred errors are observed.

Production runs explicitly skip staging-only event/payment checks and authenticated organizer checks when credentials are absent. The mutation guard itself is still tested. These skips do not verify booking, payment execution, event creation or admission; those require a separate approved staging environment and disposable accounts.

## Planned suites
Authentication, Google sign-in, organizer onboarding, current online/venue event creation, customer booking and checkout, Razorpay order/verification/webhooks, admin/RBAC, organizer data isolation, ticket inventory, scanner/QR admission, mobile API compatibility, notifications, mobile navigation, accessibility, console/network diagnostics, and post-deploy regression.

## Source-of-truth rule
DevTools tests must be derived from the current `oreodigi/booktkit` repository and its live-compatible routes, not from older BookTKIT/Eventora implementations or prior product ideas. A feature is added to the test matrix only after it is present in the current source or explicitly introduced on this repository branch.

## Current architecture covered
- Laravel website/shared backend: customer, organizer and admin panels plus API/payment endpoints.
- Flutter customer app.
- Flutter organizer app.
- Flutter scanner app.
- BookTKIT Razorpay v1 orchestration under `/api/v1/payments/*` and webhook handling.
- Existing online and venue event modes.

## Priority regression areas from the repository audit
- server-side payment/order verification and idempotency
- organizer/customer ownership isolation
- ticket/event ownership and inventory concurrency
- QR issuance/admission validation and repeated/simultaneous scans
- attachment validation/private storage
- notification authentication
- password-reset expiry and rate limiting
- mobile API endpoint compatibility across all three apps

## Hosting note
BookTKIT production is cPanel-hosted. DevTools does not assume or require Vercel. The remote MCP is a separate QA control plane and can be hosted on any suitable HTTPS Node runtime; see `REMOTE-MCP.md`.
