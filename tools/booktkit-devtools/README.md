# BookTKIT DevTools

Canonical QA tooling for oreodigi/booktkit. Production is read-only; mutation suites require isolated staging and BOOKTKIT_ALLOW_MUTATIONS=true.

## Current suites

Run with `node src/cli.js <suite>`.

Core suites: smoke, auth, organizer, event-creation, pos, staff-rbac, admission, security, scanner, contracts, checkout, razorpay, api, diagnostics, mobile, and all.

The staging release gate requires smoke/auth/organizer/event-creation/POS/staff-RBAC/admission with exact expected counts and zero failures, skips, or flakes. Event creation includes online, venue, and Box Office scenarios. Box Office is correctly modeled as a venue event with box_office_enabled=1, locations, and re-entry policy.

## Evidence semantics

A green suite certifies only the behavior listed in TEST-MATRIX.md. Contract/boundary probes must not be described as complete lifecycle testing.

Full admission lifecycle testing still requires deterministic staging fixtures for a real issued ticket and assigned staff. Until that fixture layer is implemented, admission certifies authorization/invalid-token boundaries only.

## Safety

Production browser smoke must not create users/events/bookings/payments/refunds/admissions. Mutation suites use test.booktkit.com, disposable test data, and explicit mutation enablement. Never upload auth storage or secrets.

## CI / MCP

GitHub Actions executes bounded Playwright suites. The BookTKIT MCP exposes dedicated POS, staff-RBAC, admission and existing suite controls. Every result must report environment, commit, exact pass/fail/skip/flaky counts, and artifacts. A skipped/unavailable suite is never a pass.
