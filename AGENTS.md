# BookTKIT Agent Entry Point

Last reconciled with canonical GitHub main: 6 October 2026.

Read, in order:
1. `project instructions.md`
2. `docs/CANONICAL-ARCHITECTURE.md`
3. `docs/OPERATIONS-SOURCE-OF-TRUTH.md`
4. Relevant subsystem AGENTS/docs.

## Canonical scope
Only repository: `oreodigi/booktkit`. The old `sourcecatch-konnect/eventora` project is scrapped and must never supply architecture, schema, Supabase/Vercel, or implementation assumptions.

## Current platform
BookTKIT is now more than basic ticketing. Current main includes:
- Laravel website/shared backend, admin and organizer panels.
- Customer, organizer and scanner Flutter apps.
- Unified event create/edit/duplicate domain flow and shared event wizard.
- Event types: online, venue and box office.
- Box Office POS with authoritative pricing/inventory, holds, cash shifts, staff operations, reports, ticket printing/email and POS settings.
- Organizer workforce/RBAC with assignment-scoped POS/scanner access.
- Payments V2 with fee rules, additional fees, settlement preference, Razorpay Route eligibility and automatic BookTKIT-managed fallback.
- Secure issued tickets and unified Access admission engine.
- QR/RFID/NFC/physical credential inventory, issue/collection, replacement, gates/zones, entry/exit/re-entry, supervisor overrides and audit.
- Multi-day pass products and date-scoped entitlements.
- Organizer AI credits and current OpenAI/Gemini image generation integrations.
- Mobile Homepage Studio and managed image/video hero banners.
- Playwright/MCP/ChatGPT DevTools plus isolated staging QA.

## Maintained applications
- `source/website` — Laravel 9.x website/shared backend.
- `source/customer-app` — Flutter customer app.
- `source/organizer-app` — Flutter organizer app.
- `source/scanner-app` — Flutter scanner/access app.
- `tools/booktkit-devtools` — regression, diagnostics and remote test orchestration.
- `deploy` — cPanel deployment/rollback tooling.

## Critical invariants
Server owns pricing, fees, payment state, settlement routing, booking finalization, inventory, ticket issuance, pass entitlements, credential assignment and admission. Preserve organizer/staff ownership boundaries, idempotency and transactional locking.

Do not use `bookings.scanned_tickets` or client success flags as new admission authority. Current access work resolves real issued tickets/credentials through the unified admission engine.

## Mobile
Before Flutter changes read `docs/mobile/CODEX-MOBILE-DEVELOPMENT.md`, `docs/mobile/MOBILE-API-CONTRACT.md` and the app AGENTS file. Reconcile current backend routes before coding.

## Evidence
Current code and verified runtime evidence outrank historical Markdown. A Git commit is not proof of production deployment. Never claim tests/deployment/live behavior without evidence.
