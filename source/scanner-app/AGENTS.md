# BookTKIT mobile Codex instructions
Read ../../AGENTS.md, ../../project instructions.md, ../README.md, and ../../docs/mobile/CODEX-MOBILE-DEVELOPMENT.md plus MOBILE-API-CONTRACT.md before edits.
This app shares the current Laravel backend in ../website with the other two apps. Fetch current oreodigi/booktkit code safely; reconcile API routes/controllers and DTOs. Preserve purchased archives and local branding. Do not use the old Eventora/Supabase/Vercel architecture.

## Scanner-specific requirements
Inspect lib/services/api_client.dart and backend routes/scanner_api.php plus ScannerApi controllers.
Scanner routes are mounted under /api/scanner, with distinct organizer/admin guards. Verify actual login roles and token schemas; web super-admin access does not automatically authorize these guards.
Only display authorized events. Validate QR and record admission atomically on the server. Handle invalid, wrong-event, wrong-organizer, already-used, expired and concurrently-scanned tickets. Do not trust decoded QR contents or local success flags.
Handle camera denial, network loss, expired session and duplicate camera frames. No offline admission unless a deliberate audited reconciliation protocol is implemented.
Centralize staging URL configuration; notifications are optional. Run analysis, meaningful tests, Android debug build and device/emulator camera/scan verification against the same issued customer ticket.
