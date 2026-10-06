# BookTKIT DevTools Test Matrix

Reconciled with product architecture: 6 October 2026.

Production is read-only by default. Mutation suites run only against isolated staging/test data.

## Priority suites
| Domain | Required coverage |
|---|---|
| Public/customer | discovery, auth, event details, ticket/pass selection, free/paid checkout recovery |
| Event management | online/venue/box-office create/edit/duplicate, dates, media, type-specific validation |
| Organizer isolation | cross-organizer event/booking/team/finance denial |
| Workforce/RBAC | staff login, assignments, POS/scanner permissions, archived staff history |
| Box Office POS | quote, inventory, variations, passes, idempotency, holds, shifts, print/email |
| Payments V2 | authoritative amount, fee lines, Razorpay verification, settlement fallback, webhook idempotency |
| Tickets | issued-ticket creation, customer linkage, secure token, delivery recovery |
| Access credentials | inventory, issue/collection, duplicate assignment, replacement/revocation |
| Admission | organizer/staff scanner, gate discovery, ENTRY/EXIT, re-entry, wrong gate, override audit |
| Pass admission | date-scoped entitlement, wrong date denial, multi-day ranges |
| Mobile contracts | current route/auth/request/response compatibility for all three Flutter apps |
| Security | uploads, ownership, auth, forged totals/payment/QR state, concurrency |

## Access regression minimum
Test invalid token, revoked/replaced credential, wrong event/organizer/gate, staff without assignment/permission, already-inside ENTRY, already-outside EXIT, exhausted re-entry, pass date mismatch, concurrent duplicate scan and supervisor override audit.

## Payment/POS regression minimum
Test duplicate idempotency key scope, last inventory concurrency, free pass entitlement, paid pass finalization, missing optional translation/date recovery, authenticated customer preservation and POS operation without payout/KYC UI coupling.

Never report a skipped/unavailable suite as passed. Record environment, commit and exact failures.
