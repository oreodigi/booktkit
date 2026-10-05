# BookTKIT DevTools

Verified 2026-10-05 UTC (6 October IST). Canonical repository: oreodigi/booktkit.

## Run suites

Use Node 22. Install with `npm ci` and `npx playwright install chromium`. The default target is https://test.booktkit.com; staging requires private HTTP Basic credentials. Full/mobile suites also need Firefox and WebKit installed.

```sh
node src/cli.js smoke
node src/cli.js auth
node src/cli.js organizer
node src/cli.js event-creation
npm run test:mobile
node src/cli.js all
node --test unit/runner.test.js
```

Production must be explicitly selected via BOOKTKIT_BASE_URL=https://booktkit.com and permits only the dedicated smoke suite. The Playwright configuration independently restricts production to that file. Browser smoke blocks non-GET/HEAD/OPTIONS requests and makes no login, signup, payment or event changes.

Default browser: desktop-chromium. Auth suites additionally create organizer/customer/admin Chromium projects using fresh role storage states from src/auth.js. All/mobile explicitly select the other engines/devices. Retries: one; per-test timeout: 45 seconds; Playwright global timeout: 20 minutes; runner hard timeout: 15 minutes, configurable with BOOKTKIT_RUN_TIMEOUT_MINUTES (maximum 20). The runner kills the entire process group and streams redacted output to private per-run logs.

Local MCP `start_suite` returns a run_id immediately. `get_run_status(run_id)` returns state/counts and `get_run_log(run_id, tail)` returns a bounded log tail. Existing named tools also start asynchronously. The remote MCP dispatches GitHub staging workflows with a correlation ID and provides the same polling tools; HTTP calls have 20–25 second deadlines. GitHub logs become downloadable after a job completes, not while it runs.

Artifacts are under artifacts/runs/RUN_ID. Auth files are private and must never be uploaded. CI uploads only sanitized summaries/status/logs for three days; staging traces/video are disabled. Skips, flaky tests, failures and passes are counted separately. A setup failure is a failed run even if no test cases started.

## GitHub

- booktkit-devtools.yml: production read-only Chromium smoke on main push, 5-minute job limit.
- booktkit-e2e.yml: staging-only workflow_dispatch or nightly at 20:30 UTC; multi-browser only for all/mobile.
- booktkit-phpunit.yml: PHP 8.3, isolated MySQL 8 service and schema-backed PHPUnit, 10-minute job limit.

Fourteen credential secrets were written securely: HTTP Basic username/password and username/email/password for customer, organizer, admin and scanner. Values are never committed. BOOKTKIT_ALLOW_MUTATIONS=true is only allowed on the exact test.booktkit.com origin. Payment execution remains separately disabled by default; no live charges are part of these runs.

## Current verified coverage

Server-run staging evidence after repairs: smoke 5 passed, auth 3 passed, organizer 1 passed, actual venue/online event saves for both admin and organizer 4 passed. Each has zero failures, flaky cases and skips. The runner timeout/asynchronous regression passed. PHPUnit on the isolated local MySQL test DB passed 5 tests / 12 assertions. Final CI/deployment evidence is maintained in deploy/SETUP-STATUS.md.

Event tests navigate each wizard step, exercise multiple-date visibility, select locations through the real AJAX controls, upload/crop thumbnail and gallery images, and submit the event. Helpers cover auth, wizard navigation and fixed fixture lookup. They create disposable staging events, not production events.

PHPUnit is restricted to APP_ENV=testing and DB_DATABASE=booktkit_test before application bootstrap, rejects cached config, and validates the resolved connection after bootstrap. The schema-only database/schema/mysql-schema.sql contains no application data. mysql-baseline.json records migration names already represented by the dump; RefreshLegacyDatabase loads the schema, registers that baseline, applies later migrations, and wraps each test in a transaction. Never run these tests against production credentials or a production database.

Limitations: the complete multi-browser/payment/scanner regression is broader than the verified core suites. A pass in smoke/auth/event creation does not establish payment settlement, real CAPTCHA scoring or QR admission correctness. Manual rollback has not been exercised against production.
