# BookTKIT customer app instructions

Read root `AGENTS.md`, `project instructions.md`, `source/README.md`, `docs/mobile/CODEX-MOBILE-DEVELOPMENT.md` and `docs/mobile/MOBILE-API-CONTRACT.md`.

Use the current Laravel backend; never use Eventora/Supabase/Vercel assumptions.

## Current requirements
- Checkout must use server-owned pricing/order/verification, idempotency and minor units.
- Reconcile online, venue and box-office event visibility, dates, variations and current pass products before adding UI.
- Customer bookings/tickets must resolve real `issued_tickets`; credential-required events should eventually expose collection/credential state through documented APIs rather than local assumptions.
- Payments V2 settlement/fees are backend concerns; display returned customer totals/fee lines.
- Homepage work must honor current Mobile Homepage Studio/hero configuration exposed by actual APIs.
- Auth tokens and booking ownership are server-enforced. Notifications must never gate booking/login.
- Do not infer payment success from WebView/SDK alone.

Test against isolated staging: discovery -> auth -> server-confirmed purchase/free booking -> booking/ticket visibility -> organizer visibility -> scanner/access admission. Run format/analyze/tests/debug build for changed app code.
