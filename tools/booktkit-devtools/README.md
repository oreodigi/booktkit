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
npm install
npx playwright install chromium
npm run test:smoke
npm run mcp
```

Set `BOOKTKIT_BASE_URL` to staging when tests may mutate data. Never commit credentials.

## MCP client
Run `npm run mcp` from this directory. The current transport is stdio for local development. A protected HTTPS MCP transport will be added for the ChatGPT Project after the test API and authentication boundary are finalized.

## Planned suites
Authentication, Google sign-in, organizer onboarding, event creation, special-event pricing, checkout/Razorpay test mode, admin/RBAC, mobile navigation, accessibility, console/network error collection, and post-deploy regression.
