# BookTKIT ChatGPT Testing Integration

Source of truth: current `oreodigi/booktkit` only.

## Routing
Use the protected BookTKIT testing tools/allow-listed suites for auth, public/mobile, organizer/event, checkout/payment, POS, scanner/access, security and full regression where available. Add or extend tests only after verifying the current repository contract.

## Current architecture awareness
As of 6 October 2026, tests must treat Box Office POS, workforce/RBAC, Payments V2, issued tickets, pass entitlements and the unified Access credential/admission engine as current backend domains. Scanner tests should cover staff/organizer sessions, gates and explicit entry/exit rather than only legacy one-way QR status.

## Safety
Production is read-only. Never create events, bookings, payments, refunds, users, credentials or admissions on production from automated tests.

State-changing E2E requires an approved isolated staging BookTKIT environment, disposable accounts/data, explicit mutation enablement and test-mode payment configuration where applicable.

## Reporting
Every run reports target, commit when available, suite, exit status, passed/failed/skipped/flaky counts and failure artifacts. Do not equate a limited smoke pass with full platform verification.
