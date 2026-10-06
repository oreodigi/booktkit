# BookTKIT — Project Instructions

Last reconciled with canonical GitHub main: 6 October 2026.

## Mission
BookTKIT is a production event-ticketing, Box Office and venue-access platform. Founder/Product Owner defines outcomes; the technical organization inspects current code, designs, implements, tests, deploys and verifies.

## Canonical project
ONLY repository: `oreodigi/booktkit`.
SCRAPPED: `sourcecatch-konnect/eventora`. Never import its Next.js/Supabase/Vercel assumptions, schemas or decisions.

## Repository
- `source/website`: Laravel 9.x / PHP 8.3 website, APIs, admin and organizer panels.
- `source/customer-app`, `source/organizer-app`, `source/scanner-app`: Flutter apps sharing Laravel backend.
- `tools/booktkit-devtools`: Playwright/MCP/ChatGPT QA.
- `deploy`: deployment/rollback tooling.
- `docs/mobile`: mobile contracts.
- `project-review`: historical evidence; never override current code/runtime.

## Current product domains
The maintained backend now includes:
- online, venue and box-office events;
- shared event create/edit/duplicate services and wizard;
- ticket/date/variation inventory and multi-day pass products;
- Box Office POS, holds, cash shifts, staff POS, reports and settings;
- organizer team/RBAC with event/location/gate assignment;
- Payments V2 fee rules, POS/additional fees, ledger/reconciliation, Razorpay Route and BookTKIT-managed fallback;
- secure issued tickets and ticket delivery;
- Access credentials (QR wristband, RFID wristband/card, NFC card, QR badge, physical ID), collection/assignment/replacement;
- unified entry/exit/re-entry admission, gates, zones, live operations and audited overrides;
- organizer AI credit/package flows and image generation;
- Mobile Homepage Studio and managed image/video hero banners.

## Authority and invariants
Current code on the relevant branch, verified runtime, canonical docs, project conversation, then historical reports — in that order.

Server must remain authoritative for price, discounts, tax, fees, currency, settlement mode, payment state, booking finalization, ticket issuance, pass entitlements, credential state and admission. Enforce customer/organizer/staff/admin ownership server-side. Use migrations. Make financial/admission operations idempotent and concurrency-safe.

`issued_tickets` plus the unified access/admission services are the entitlement/admission authority. Do not reintroduce arbitrary legacy `scanned_tickets` mutation as a competing source of truth.

## Hosting
Production: `booktkit.com`; host `server.tejum.cloud`; cPanel account `booktkit`; live path `/home/booktkit/public_html`. Keep all work isolated to BookTKIT and never expose secrets.

## Mobile
Read `docs/mobile/CODEX-MOBILE-DEVELOPMENT.md`, `docs/mobile/MOBILE-API-CONTRACT.md` and scoped AGENTS. Do not assume every web feature has native API/UI parity. Scanner work must use real issued tickets/credentials, gate context and explicit entry/exit semantics.

## Git, deployment and testing
Workflow: inspect -> implement coherent change -> test -> review diff -> commit/push -> deploy with BookTKIT tooling -> controlled migration/build/cache -> verify deployed commit and live/staging flow -> repair/rollback.

Production tests are read-only by default. State-changing regression belongs on isolated staging. Prioritize auth, organizer/staff isolation, event forms, POS/inventory, payments/webhooks, passes, ticket issuance, credentials, entry/exit/re-entry, QR/RFID admission and mobile compatibility.

A task is DONE only when applicable implementation, tests, Git update, intended deployment and verification are complete. State exactly what was and was not verified.
