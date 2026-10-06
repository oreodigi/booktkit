# BookTKIT Canonical Architecture

Last reconciled with `oreodigi/booktkit` main: 6 October 2026.

## System
BookTKIT is a Laravel 9.x / PHP 8.3 event-commerce and venue-operations platform with Blade/Laravel Mix web UI plus three Flutter applications. Laravel is the shared authority for web/mobile/POS/scanner behavior.

## Applications
- `source/website`: public web, customer/organizer/admin auth, APIs, organizer/admin panels, checkout, POS, payments, tickets and Access.
- `source/customer-app`: customer discovery/checkout/bookings/tickets.
- `source/organizer-app`: organizer mobile client; API parity must be verified feature-by-feature.
- `source/scanner-app`: organizer/staff access client.
- `tools/booktkit-devtools`: Playwright, diagnostics, MCP and ChatGPT test orchestration.
- `deploy`: cPanel staging/production deployment tooling.

## Event domain
Canonical event types are `online`, `venue` and `box_office`. Create/edit/duplicate share domain validation/services and common wizard assets. Event type selected by route is authoritative; venue coordinates are implementation data rather than required manual organizer UI.

Events support dates, tickets, variations, media and current pass products. Multi-day passes are server-priced products with date-scoped entitlements persisted through checkout/POS/finalization and enforced by Access.

## Sales channels
Web/mobile checkout and POS must converge on server-authoritative price/inventory/payment/ticket state. Box Office is both an event capability/type and an operational sales workspace; POS itself is a sales channel for fee/accounting purposes.

Current POS includes three-column selling UI, ticket/pass products, customer/identity metadata, quote/reserve/complete flow, secure issued tickets, print/email, holds, staff sessions, cash shifts, reporting and configurable POS settings.

## Workforce/RBAC
Organizer staff identities are separate operational users with organizer ownership, assignments, departments/roles and granular permissions. POS/scanner/access actions must enforce organizer + event/location/gate assignment server-side. Deleting/archive operations must preserve financial/audit history.

## Payments V2
The server owns pricing, fee rules, additional fees, settlement routing, payment orders, finalization, ledger, refunds/reversals, reconciliation and ticket issuance.

Settlement modes:
- BookTKIT Managed — BookTKIT collects and later settles organizer payable.
- Razorpay Direct/Route — used only when current linked-account/KYC/split eligibility permits.

Organizer preference never overrides eligibility. Ineligible Direct automatically falls back to Managed for new orders. Orders snapshot actual settlement mode and fee lines.

## Tickets, passes and delivery
`issued_tickets` are the canonical attendee entitlement records. Ticket issuance uses secure opaque/HMAC bearer tokens and server-side token hashes. Booking success must preserve authenticated customer linkage. Ticket delivery is a post-finalization service and cannot define payment success.

Pass products create explicit entitlements; admission checks the entitled event date.

## Access & credentials
Physical/digital credentials are separate from commercial ticket entitlement. Supported domain types include QR wristband, RFID wristband/card, NFC card, QR badge and physical ID.

Current main includes credential batches/inventory, ticket assignment, collection desk, replacement/revocation, replacement fee reference, access policies, gates, zones, scan ledger, live operations, staff scanner sessions and audited supervisor overrides.

The unified admission engine resolves ticket or active credential to an issued ticket and performs transactional, assignment-scoped, gate-aware `entry`/`exit` transitions with re-entry/date/pass enforcement. Legacy arbitrary scan-status mutation is retired from the new authority path.

## AI and presentation
Organizer AI credits/packages support free activation and paid purchase paths. Image generation integrations use current OpenAI GPT Image and current Gemini image APIs/response formats, with credit estimates shown in organizer UX.

Public presentation includes managed multi-banner image/video hero content and Mobile Homepage Studio campaigns/templates/versioning.

## Authority
Server authority is mandatory for money, inventory, entitlements, credential lifecycle and admission. Use transactions/locks/unique constraints for concurrency-sensitive operations. Never trust client totals, payment flags, QR payload claims or UI permissions.

## Non-canonical
`sourcecatch-konnect/eventora` is scrapped. Historical `project-review` documents are evidence only and must be reconciled with current code/runtime.
