# BookTKIT mobile Codex instructions
Read ../../AGENTS.md, ../../project instructions.md, ../README.md, and ../../docs/mobile/CODEX-MOBILE-DEVELOPMENT.md plus MOBILE-API-CONTRACT.md before edits.
This app shares the current Laravel backend in ../website with the other two apps. Fetch current oreodigi/booktkit code safely; reconcile API routes/controllers and DTOs. Preserve purchased archives and local branding. Do not use the old Eventora/Supabase/Vercel architecture.

## Organizer-specific requirements
Inspect lib/app/urls.dart, organizer API controllers and auth:organizer_sanctum. Use real organizer-owned events, tickets, bookings and financial data; enforce isolation on server.
Match current event/ticket/date/seat schemas and validation; do not recreate obsolete special-event features as if already present.
Read current /api/v1/organizer/payments/settings schema, settlement eligibility and fee behavior. Display backend-calculated finance; split settlement is available only when server eligibility permits it.
Centralize configurable staging URLs before mutations. Reuse current architecture, handle expired sessions/validation and avoid duplicate updates.
Run analysis, meaningful tests, Android debug build and two-organizer isolation checks. Confirm customer purchases appear in organizer bookings and authorized scanner access agrees.
