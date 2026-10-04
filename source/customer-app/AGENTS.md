# BookTKIT mobile Codex instructions
Read ../../AGENTS.md, ../../project instructions.md, ../README.md, and ../../docs/mobile/CODEX-MOBILE-DEVELOPMENT.md plus MOBILE-API-CONTRACT.md before edits.
This app shares the current Laravel backend in ../website with the other two apps. Fetch current oreodigi/booktkit code safely; reconcile API routes/controllers and DTOs. Preserve purchased archives and local branding. Do not use the old Eventora/Supabase/Vercel architecture.

## Customer-specific requirements
Inspect lib/app/urls.dart and lib/features/checkout before building. Adapt checkout to current server-owned orders/verification, explicit minor units and idempotency. Reconcile free tickets, seats, coupons and date/variation support with backend; do not silently bypass missing support.
Integrate current homepage configuration through an actual API; disabled sections and active seasonal templates should agree with backend settings.
Verify email/password and Google native login against current backend token issuance. Notifications must not gate checkout. Repair permissive WebView paid detection and environment leakage. Protect customer tokens/data and booking ownership.
Run analysis, meaningful tests and Android debug build; verify purchase -> server booking -> organizer visibility -> scanner admission using staging. Release signing must not use debug keys.
