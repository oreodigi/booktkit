---
name: booktkit-testing
description: Deterministic QA for BookTKIT through its protected MCP and Playwright regression framework.
---

# BookTKIT Testing

Use this skill for testing the current `oreodigi/booktkit` system. Never infer routes or behavior from old Eventora repositories.

## Tool routing
- login -> `booktkit_test_login`
- signup -> `booktkit_test_signup`
- organizer workflow -> `booktkit_test_organizer_flow`
- event creation -> `booktkit_test_event_creation`
- Box Office POS -> `booktkit_test_pos`
- workforce/staff authorization -> `booktkit_test_staff_rbac`
- admission/scanner boundaries -> `booktkit_test_admission`
- checkout/payment -> `booktkit_test_checkout`
- mobile/responsive -> `booktkit_test_mobile`
- console/page/network errors -> `booktkit_get_runtime_errors`
- full regression -> `booktkit_run_regression`
- arbitrary approved suite -> `booktkit_start_test_run`
- status -> `booktkit_recent_test_runs`

## Safety
Production is read-only. Never weaken mutation guards. State-changing event/payment/admission tests require an approved non-production BookTKIT subdomain, disposable accounts, explicit mutation enablement, and Razorpay test mode.

## Reporting
Report target, suite, run status, failures, and artifact/run link. Distinguish boundary/contract coverage from complete lifecycle coverage. Never call admission end-to-end until seeded issued-ticket/staff fixtures execute ENTRY, EXIT and re-entry.
