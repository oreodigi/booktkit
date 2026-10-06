---
name: booktkit-testing
description: Deterministic QA for the current BookTKIT platform through protected MCP and Playwright regression tooling.
---

# BookTKIT Testing

Use only current `oreodigi/booktkit` contracts. Never infer behavior from Eventora.

Current QA domains include auth, event management, Box Office/POS, organizer workforce/RBAC, Payments V2, issued tickets, pass entitlements, credential lifecycle and organizer/staff gate admission.

## Tool routing
Use the available BookTKIT tools for login/signup, organizer/event workflows, checkout/payment, mobile/responsive, runtime errors, scanner/access and full regression. Prefer the narrowest applicable suite, then broader regression for cross-domain changes.

## Safety
Production is read-only. State-changing event/payment/POS/credential/admission tests require isolated staging, disposable accounts, explicit mutation enablement and Razorpay test mode when payment execution is involved.

## Reporting
Report target, suite, run status, pass/fail/skip/flaky counts and artifacts. Distinguish verified coverage from untested domains.
