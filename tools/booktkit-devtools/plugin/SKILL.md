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
- checkout/payment -> `booktkit_test_checkout`
- mobile/responsive -> `booktkit_test_mobile`
- console/page/network errors -> `booktkit_get_runtime_errors`
- full regression -> `booktkit_run_regression`
- arbitrary approved suite -> `booktkit_start_test_run`
- status -> `booktkit_recent_test_runs`

## Safety
Production is read-only. Never weaken mutation guards. State-changing event/payment/admission tests require an approved non-production BookTKIT subdomain, disposable accounts, explicit mutation enablement, and Razorpay test mode.

## Reporting
Report target, suite, run status, failures, and artifact/run link. Distinguish passing read-only coverage from skipped state-changing coverage.
