# BookTKIT DevTools

Canonical QA tooling for `oreodigi/booktkit`. Production is read-only by default; state-changing suites require isolated staging and explicit mutation enablement.

## Current product coverage target — 6 October 2026
DevTools must track the current platform, including:
- customer/organizer/admin auth;
- online, venue and box-office event forms;
- organizer team/RBAC and assignment boundaries;
- Box Office POS, holds, shifts, passes and inventory concurrency;
- Payments V2 orders/verification/fallback/fees;
- issued-ticket/customer linkage;
- credential issue/replacement/revocation;
- organizer/staff scanner sessions, gate discovery and ENTRY/EXIT/re-entry;
- pass/date admission;
- mobile API contracts and responsive web.

See `TEST-MATRIX.md` for required regression coverage.

## Safety
Production browser smoke must not create users/events/bookings/payments/refunds/admissions. Mutation suites use `test.booktkit.com`/approved isolated staging, disposable accounts, Razorpay test mode where applicable and explicit `BOOKTKIT_ALLOW_MUTATIONS=true`.

## Execution
The local/remote MCP exposes allow-listed suites and asynchronous run IDs/status/log retrieval. GitHub Actions executes bounded Playwright/PHPUnit workflows. Preserve redaction and never upload auth storage or secrets.

A passing older smoke/auth/event suite does not prove the newer POS/Payments/Access/pass domains. Report exact suite, environment, commit, pass/fail/skip counts and artifacts.
