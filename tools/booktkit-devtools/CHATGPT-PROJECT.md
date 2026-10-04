# BookTKIT ChatGPT testing integration

Source of truth: current `oreodigi/booktkit` repository only.

## Safe commands exposed to ChatGPT
- health: public smoke suite
- auth: customer and organizer authentication surfaces
- mobile: responsive browser suite
- api: Laravel API surface
- contracts: customer/organizer/scanner mobile API contracts
- organizer: current organizer and Online/Venue event surfaces
- razorpay: BookTKIT Razorpay v1 validation/test-mode coverage
- scanner: scanner API contract
- security: anonymous boundary and mutation-guard checks
- all: complete approved suite

Do not infer features from previous BookTKIT/Eventora repositories or prior product discussions. Add a test only after the feature is present in this repository.

## Production policy
Production is read-only. Never create events, bookings, payments, refunds, withdrawals, users, or QR admissions against production from automated tests.

## Mutation policy
State-changing E2E tests require:
1. a non-production `*.booktkit.com` environment,
2. dedicated disposable test accounts/data,
3. Razorpay test mode for payment tests,
4. `BOOKTKIT_ALLOW_MUTATIONS=true`,
5. explicit staging workflow/tool support.

The current GitHub workflow intentionally forces mutations off.

## Result contract
Every run should return:
- suite and target
- exit status
- failing test names/output
- screenshots/traces/videos for failures
- HTML Playwright report
- structured `test-summary.json`

The remote MCP control plane should trigger these approved suites rather than exposing arbitrary shell commands or arbitrary URLs.
