# BookTKIT mobile development — Codex / VS Code
Reviewed against GitHub main on 5 October 2026. This document is a starting point, not proof of production compatibility. Recheck the latest commit and deployed API before each phase.

## Canonical system
- Repository: oreodigi/booktkit. Do not use sourcecatch-konnect/eventora or its Next.js/Supabase assumptions.
- Shared Laravel backend: source/website. Live hosting: cPanel under server.tejum.cloud; not Vercel. Keep the existing database/authentication architecture.
- Flutter apps: source/customer-app, source/organizer-app, source/scanner-app.
- Desktop checkout: C:\Users\pradeep\Desktop\Booktikt. Preserve Source Code archives, local logos and purchased documentation.
- Start with root AGENTS.md, project instructions.md, source/README.md, this guide and MOBILE-API-CONTRACT.md. Older reports are findings to reconcile, not instructions to restore old behavior.
- GitHub main, feature branches and deployed production can differ. Record commit SHA, deployment evidence and API base used. Read current PRs and migrations where accessible.

## Start every work session
1. Check git status, branch, remote and local changes. Fetch/pull with --ff-only when clean; preserve uncommitted work. Never reset --hard, clean, overwrite branding, or force push.
2. Inspect changes since the last integration baseline. Review routes, controllers, validation, models, migrations, auth guards, payment services and app DTOs together.
3. Run flutter --version, flutter doctor -v, dart --version and inspect each pubspec.yaml, lockfile and Android Gradle configuration. Resolve compatible toolchains from actual constraints; do not perform wholesale dependency upgrades.
4. Build a compatibility matrix: feature, app service/model, HTTP method/path, auth guard, request/response, backend source, deployed evidence, test, status. Mark unknowns explicitly.
5. Identify missing API behavior before implementing screens. Website behavior is not automatically exposed to native apps. Add compatible server APIs with tests where required; never regress the working website to fit old mobile code.

## Product changes to reconcile
- Razorpay server orders and verification, BookTKIT-managed funds versus eligible organizer split settlement, and included/additional platform fees.
- Current email/password, Google login and reCAPTCHA v3 changes. Confirm which API paths received them. Browser redirects/cookies are not a native login token exchange.
- New mobile homepage sections, admin enable/disable/style settings, seasonal/festival templates. Inspect their actual status and API coverage; do not invent completed endpoints.
- Current event/ticket variations, dates, seating, coupons and attendee fields. Historical festive-event work from the obsolete codebase is not evidence these features exist here.
- DevTools and JavaScript fixes may affect the web only. Preserve them when adding mobile integration.

## Engineering rules
Use one shared backend and real data across all apps. Keep the existing Flutter architecture unless a change is justified. Centralize environment URLs; make organizer/scanner configurable too. Check every request, image, OAuth callback, payment WebView and helper URL for production leakage. Customer API_BASE_URL currently includes /api; PGW_BASE_URL includes /pgw.
Never embed database passwords, gateway secrets, webhook secrets, access tokens, signing keystores or production .env files. Inject owned configuration safely; do not log tokens or personal data.
Derive identity and permissions server-side. Use appropriate customer/organizer/admin guards, secure token storage, logout cleanup, 401 recovery and cross-account isolation. A super-admin web role does not imply a scanner or mobile guard supports it.
Use typed contracts, explicit currency/minor units, null-safe parsing, timeouts, controlled retries and readable errors. Avoid duplicate submissions and retries of non-idempotent mutations.
Notifications are optional: booking/login/scanning must work with disabled Firebase, denied permission or token acquisition failure. Add owned Firebase only when configured; never restore supplier credentials.
Server owns prices, discounts, tax, fees, reservations and final paid/admitted states. SDK success, URL fragments and client-provided payment_status are insufficient.
Support additive API changes and older clients. If a breaking change is unavoidable, version it and document a migration.

## Phases and completion gates
1. Baseline: reconcile latest web/API/app code, document matrix and reproducible toolchain.
2. Customer: auth, discovery, current home configuration, tickets/seats, server-owned checkout, bookings and QR display.
3. Organizer: auth, event/ticket management, bookings, finance/payment settings and enabled backend capabilities.
4. Scanner: role-correct login, allowed events, online server validation, repeat/concurrent scan handling and ownership.
5. Release: meaningful tests, Android device/emulator verification, signed APK/AAB and release notes. iOS build/signing requires macOS/Xcode; do not claim it from Windows.

Per changed app run flutter pub get, dart format on changed files, flutter analyze and meaningful flutter test/integration tests, then flutter build apk --debug. Resolve missing local branding assets without removing branding. Release artifacts require owned signing, unique application IDs, version increments and HTTPS configuration. Use staging and Razorpay test mode for transactions; production mutations require the authorized deployment scope and backups.
Test customer purchase -> server-confirmed booking -> organizer visibility -> scanner admission. Include denied notifications, expired auth, wrong organizer/customer, invalid/repeated QR, cancellation, pending payment, duplicate callbacks and last-ticket concurrency.
Record results in docs/mobile/PROGRESS.md and docs/mobile/COMPATIBILITY.md as work proceeds; create those when implementing, not with invented results. Include commands, commit, environment, failures and unresolved blockers. A compiling app alone is not production-ready.

## First prompt in VS Code
Read AGENTS.md and docs/mobile/CODEX-MOBILE-DEVELOPMENT.md. Synchronize safely with oreodigi/booktkit main, inspect the current backend and all three Flutter apps, and build the API compatibility matrix. Then implement customer integration first, organizer integration second and scanner integration third. Preserve current website functionality and use staging for writes. Continue through appropriate tests and Android builds; report exact evidence and any required owned credentials or signing inputs. Do not assume old supplier APIs or old Eventora requirements match this system.
