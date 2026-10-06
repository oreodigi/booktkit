# BookTKIT maintained source

Current source layout:
- `website` — Laravel website/shared backend and operational panels.
- `customer-app` — Flutter customer app (`com.booktkit.customer`).
- `organizer-app` — Flutter organizer app (`com.booktkit.organizer`).
- `scanner-app` — Flutter scanner/access app (`com.booktkit.scanner`).

As of 6 October 2026 the Laravel backend also owns Box Office POS, workforce/RBAC, Payments V2, issued tickets, multi-day passes, physical credential lifecycle and unified access admission. Mobile code must reconcile against those current contracts instead of older supplier flows.

Use staging overrides for development. Never embed production secrets, gateway credentials, Firebase secrets, signing material or private environment values. Customer/organizer/scanner must share server-authoritative pricing, booking, ticket and admission state.

Purchased/supplier archives are provenance only. Retired supplier installer/update behavior must not be restored. App signing, owned Firebase configuration and store identifiers require owned release configuration.

Read root `AGENTS.md`, `project instructions.md` and `docs/mobile/*` before changes.
