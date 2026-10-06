# BookTKIT DevTools Test Matrix

Reconciled with staging: 6 October 2026.

Production remains read-only. State-changing suites run only against isolated staging.

## Release-gate suites

| Suite | What a pass certifies |
|---|---|
| smoke | Public read-only production-safe routes respond correctly |
| auth | Authenticated role bootstrap and protected dashboard access |
| organizer | Organizer authenticated dashboard baseline |
| event-creation | Online, venue, and Box Office event creation completes through the wizard |
| pos | Authenticated organizer POS, reports, and shifts load without payout/KYC coupling |
| staff-rbac | Team surface is reachable; anonymous staff web/API access is denied; scanner login validates input |
| admission | Organizer/staff scanner admission boundaries reject invalid/unauthorized tokens without 5xx |
| security | Anonymous ownership/security boundaries and mutation guards |
| scanner | Legacy organizer/admin scanner API contract probes |
| contracts | Mobile/payment/scanner API contracts |
| checkout / razorpay | Payment validation and Razorpay contract coverage |
| diagnostics | Runtime browser/network diagnostics |

## Coverage truth rule

A suite name must never imply behavior it does not execute. Green contract/boundary tests are not evidence of a complete business lifecycle.

The current admission suite is a boundary gate, **not yet** full ENTRY → EXIT → re-entry lifecycle certification. Full lifecycle certification requires deterministic seeded staging fixtures for an issued ticket plus assigned scanner staff. Until that fixture exists, reports must state this limitation explicitly.

## Required next fixture layer

The deterministic staging fixture must create an organizer-owned Box Office event, location, ticket inventory, booking, issued ticket token, sales-agent staff, ticket-checker staff, event assignments, and known re-entry policy. It must be disposable/idempotent and staging-only. Once present, admission/POS suites must cover sale → ticket issuance → scan ENTRY → EXIT → re-entry → limit denial, wrong-event/organizer denial, revoked/replaced token denial, duplicate/concurrent scan protection, and inventory concurrency.

Never report skipped, unavailable, boundary-only, or contract-only coverage as end-to-end certification.
