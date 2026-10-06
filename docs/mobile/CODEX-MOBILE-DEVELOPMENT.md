# BookTKIT Mobile Development

Reviewed against canonical main on 6 October 2026. Recheck latest backend routes and deployed API before every mobile phase.

## System
All three Flutter apps share the Laravel backend. Current backend capability has moved significantly: Box Office POS, organizer workforce, Payments V2, issued tickets, multi-day passes and the Access credential/admission engine now exist on web/backend. Do not assume native parity.

## Required start
Read root AGENTS, project instructions, this file, MOBILE-API-CONTRACT and the app-scoped AGENTS. Inspect current routes/controllers/requests/services/migrations and Flutter DTOs together.

Use isolated staging for writes. Record branch/commit/API base. Never restore Eventora/Supabase/Vercel assumptions or supplier credentials.

## Customer app
Use server-owned payment orders/verification and minor units. Reconcile pass/date/variation support before UI. Customer booking/ticket display must map to real issued tickets. Credential collection state needs an actual backend API contract before native UI claims it.

## Organizer app
Organizer data is owner-scoped. Web features such as Box Office, workforce, fee settings, passes and credential management require verified mobile endpoints before implementation. Finance must display server calculations, not reproduce them.

## Scanner app
The current backend scanner model includes organizer/staff sessions, authorized event/gate discovery, persistent gate and entry/exit mode, credential/ticket resolution, re-entry policy, pass-date enforcement and audited override.

Native scanner must send explicit direction and gate context required by the current API. Do not use legacy arbitrary scanned-status updates as authority. No offline admission without a deliberate signed reconciliation protocol.

## Engineering
Centralize environment URLs. Protect tokens. Notifications are optional. Server owns prices, payment, booking, ticket, pass, credential and admission state. Use typed contracts, timeouts and controlled retries; never retry non-idempotent mutations blindly.

## Completion
For changed app: `flutter pub get`, format, analyze, meaningful tests and Android debug build. Verify cross-app staging journey: customer purchase/free booking -> issued ticket -> organizer visibility -> authorized scanner/access entry/exit. Include wrong organizer/staff, invalid/revoked credential, repeat scan, pass-date mismatch, payment callback duplication and last-ticket concurrency.
