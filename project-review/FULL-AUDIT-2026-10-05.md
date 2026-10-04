# Booktkit repository audit — 5 October 2026

## Decision

Do not treat the current repository as release-ready. The new payment foundation adds server pricing, signatures and row locking, but legacy booking bypasses remain and the new path has recovery, delivery, ownership and accounting gaps. The mobile applications have not been integrated with the new contracts. One discovery API failure was reproduced with public read-only live requests.

This is a repository-wide static audit with targeted live GET checks, not an exhaustive penetration test or end-to-end certification. Application code was not changed. Purchased archives and branding were preserved.

## Git and instruction baseline

- Read root AGENTS.md, project instructions.md, source/README.md, both docs/mobile guides, all three scoped mobile AGENTS.md files, and the deployment/previous audit context.
- Remote: https://github.com/oreodigi/booktkit.git. `git fetch origin` succeeded.
- Local main and origin/main matched `3e14fd02047b958f29eba90e3b37cd603aae0375` before the audit branch was created. No incoming main commits were available.
- The working tree contained the untracked previous customer report. Pull was skipped under the user's clean-tree rule; no stash/reset/clean was used.
- Audit branch: `feature/full-audit-20261005`. The previous report remains untracked and is not included in this audit commit.
- Comparison baseline: `05e3f5a` (the earlier local source baseline). There are 163 reachable commits and 120 changed files, with 4,937 insertions and 431 deletions. This is a historical comparison, not newly downloaded changes in this session.
- Mobile implementation code is unchanged across that comparison; only their scoped instruction files were added. The latest shared app implementation commit in the inspected log was `cd4279c`.
- Full changed-file inventory and verification summary: FULL-AUDIT-2026-10-05-EVIDENCE.json.

Six unrelated tracked files changed during this session, without writes from this audit: analysis_options.yaml and pubspec.lock in each of the three apps. They are preserved and excluded from the audit commit. Analyzer settings add platform/build exclusions without excluding lib. Lockfiles update characters, matcher, material_color_utilities, meta, test_api and vector_math; organizer also updates intl. All three resolved Dart minima rise to >=3.11.0-0. These changes therefore need a pinned compatible Flutter/Dart toolchain and separate analyze/test/build validation; they are not cosmetic lockfile churn. Their producing command/toolchain and test outcomes are unknown to this audit. Their diffs passed whitespace checking. The committed-history count above excludes these ongoing working-tree changes.

Review covered changed backend routes, payment controllers/services/models/migration, homepage studio/resolver/migration/seeder, web auth/reCAPTCHA, selected Blade/JavaScript changes, DevTools/workflows/tests, and all three apps' key configuration/auth/checkout/scanner integration paths. Large visual CSS changes were inspected as source, not verified in rendered browsers. Existing authorization/upload/reset paths were sampled against previously identified risks.

## Findings

Priority definitions: Critical = a direct payment/security boundary violation; High = major functional/security/recovery risk; Medium = integration or operational correctness gap. Except F01, these are confirmed source observations with runtime impact inferred from those paths; live exploitability was not tested.

### F01 — High: discovery fails for unsupported language headers (live reproduced)

`source/website/app/Http/Controllers/Api/HomeController.php:33` and `Api/EventController.php:59` perform an exact language-code lookup then dereference the result. Public GET /api/ and /api/events returned 200 with Accept-Language: en, and 500 with both `*` and `invalid-audit-locale`. A browser-style list or regional locale is also not normalized in this inspected code, but those values were not tested. The customer helper normally sends a normalized language, so this is conditional, not proof all app requests fail.

Fix: normalize Accept-Language and fall back to a configured default, with a controlled error if no language exists. Regression checks should include en, en-IN, weighted lists, wildcard and unknown language.

### F02 — Critical: legacy paid-booking bypass remains reachable

`source/website/routes/api.php:53` still exposes /api/event-booking. `Api/EventController.php:896` accepts caller total/tax/discount/customer_id; lines 904–906 use caller paymentStatus and default online bookings to completed. The inspected path does not verify transaction proof before booking creation and paid ticket handling. New v1 routes do not close this path, and the customer app still uses it.

Fix: route every paid booking through trusted server verification and authoritative quotes; preserve a separately validated free/offline path. Do not remove compatibility blindly—define the migration for older clients.

### F03 — Critical: public auth configuration responses include secrets

`Api/CustomerController.php:53,306` and `Api/Organizer/OrganizerController.php:60` select google_recaptcha_secret_key, facebook_app_secret and google_client_secret and serialize Basic settings under data.bs. The inspected Basic model has no hidden-field filter for those fields; login/signup configuration routes are public. No secret-bearing endpoint was fetched during this audit, and no actual secret values were collected.

Fix: serialize an explicit public configuration DTO. Reconcile deployment; rotate credentials if exposure is confirmed, after removing the response fields.

### F04 — Critical: customer mutations accept privileged model fields

`Api/CustomerController.php:357,630` uses request.all() for account creation/profile update. `Models/Customer.php` includes status, email_verified_at, provider/provider_id, verification_token and password in fillable. These fields extend beyond the validated signup/profile fields. In particular a caller can supply email_verified_at at signup, and profile updates can accept password without the intended password-change/current-password validation.

Fix: persist validated allowlisted fields only; own verification/status/provider changes on the server. Test overposted fields, email changes and password changes. Review the corresponding web request.all() patterns as well.

### F05 — High: v1 payment identity and idempotency are not scoped

`Api/PaymentController.php:26` accepts nullable arbitrary customer.customer_id. Payment routes have no explicit customer authentication; the finalizer writes that supplied ID to the booking. `PaymentOrderService.php:8` looks up a globally unique caller idempotency_key without actor/event/request fingerprint checks. A reused key can resolve to another request's existing order.

Fix: bind authenticated identity or a secure guest purchase capability to the order; scope keys and reject conflicting payloads. Verification/retrieval must enforce the same ownership.

### F06 — High: repeated order creation replaces gateway identity

`PaymentController.php:36` always calls createOrder, even when PaymentOrderService returned an existing order. `RazorpayRouteService.php:15–20` creates another Razorpay order and overwrites gateway_order_id/status. Retrying the same idempotency key therefore is not idempotent, can invalidate proof for the earlier checkout, and can change a previously paid order back to pending. Check-then-create also has a concurrency race at the unique idempotency key.

Fix: reuse the existing gateway order and immutable quote, protect creation with locking/unique-conflict recovery, and prohibit paid-order recreation.

### F07 — High: authorization is treated as final payment

`RazorpayRouteService.php:28` accepts both authorized and captured, then verification marks the order paid and issues a completed booking. Automatic capture intent does not prove capture has completed.

Fix: capture or await trusted captured state before paid tickets/payables, while preserving a recoverable pending state.

### F08 — High: captured webhook does not fulfill the purchase

`Api/RazorpayWebhookController.php:18` sets captured_unfinalized, then invokes PaymentReconciliationService. That service only compares ledger/refund amounts; it does not finalize a booking, issue a ticket or record the paid ledger. The event is marked processed. A captured payment whose customer closes the app/browser can remain unfulfilled indefinitely. Webhook matching also does not explicitly validate stored amount/currency before state changes.

Fix: a locked, idempotent common confirmation service used by callback, webhook and retry worker, with proof validation and a reconciliation queue.

### F09 — High: gateway side effects occur inside rollbackable DB work

`Api/PaymentController.php:42–51` verifies, finalizes and performs organizer transfer within one DB transaction. A transfer failure rolls back booking/paid ledger even though customer payment already exists. A successful transfer followed by commit failure cannot be rolled back remotely. Refund operation has a similar external-call/DB-commit boundary; transfer retry lacks order locking.

Fix: commit confirmed payment/booking independently, execute settlement/refund operations through a recoverable outbox/state machine and gateway idempotency, and reconcile ambiguous failures.

### F10 — High: new finalizer omits ticket delivery and existing balances

`Services/Payments/BookingFinalizationService.php` creates a booking and stock changes, then returns. Neither the v1 verification path nor the replacement web Razorpay callback dispatches BookingInvoiceJob or invokes generateInvoice/sendMail. The web controller retains the job import without using it. The success action only renders an existing booking. Invoice can remain null; no delivery is initiated in the inspected new path.

The new ledger/organizer_amount also does not update legacy Earning/organizer balance records used by existing dashboard and withdrawal flows. This creates competing financial sources without a documented reconciliation contract.

Fix: idempotent delivery after confirmation, with retry visibility; explicitly reconcile the new ledger with legacy balance/withdrawal behavior.

### F11 — High: stock is locked only after payment, with unsupported purchase features

Quote checks availability without reserving it; finalization locks/decrements stock after gateway payment. Two purchasers can both pay for the last ticket, then one finalization throws with no compensation workflow. Duplicate item rows are checked separately during quoting instead of aggregating demand.

The v1 quote/finalizer handles ticket/variation prices but not seat reservation or coupon inputs. Customer event_date is merely a supplied date and defaults to today's date, without checking event occurrences; event active/expiry eligibility is not enforced in quote. Free tickets still enter the gateway-order path.

Fix: transactional expiring reservations, duplicate aggregation, validated event occurrence and eligibility, explicit seat/coupon contracts, a gateway-free zero-total flow, and recovery/refund for impossible fulfillment.

### F12 — High: replacement web Razorpay callback has namespace/guard errors

`FrontEnd/PaymentGateway/RazorpayController.php:72` references undefined AppModelsEventBooking on the already-paid path. Lines 80/84 use unqualified Throwable without importing it, so those catches do not target global PHP Throwable. Line 53 uses auth().id() rather than explicit customer guard; config/auth.php defaults to web/users, so this path does not reliably associate the customer session. Its microtime-based idempotency key creates a different order on every submission.

Fix: correct imports/fully qualified names, use auth:customer explicitly, stable checkout idempotency and reliable duplicate callback recovery.

### F13 — High: ledger and financial totals do not fully reconcile

PaymentLedgerService records customer_total, negative organizer_amount and negative platform_fee only. Tax is unallocated. An included fixed fee can exceed ticket value while organizer_amount is clamped to zero, leaving inconsistent totals. Refunds/transfers have no balancing ledger entries. Reconciliation compares only the customer entry/refund ceiling and ignores transfer correctness; finance summaries sum paid orders without subtracting refunds. The ledger migration has no unique payment_order_id/entry_type constraint for its firstOrCreate contract.

Fix: define complete accounting entries and totals, cap/validate fees where appropriate, reconcile refunds/transfers and enforce database uniqueness.

### F14 — High: changing managed settlement to split is circular

`OrganizerPaymentSettingsController.php:20` tests p.canSplit() before changing settlement_mode. canSplit() itself requires settlement_mode == razorpay_split. An otherwise eligible managed organizer therefore cannot switch via this API. Admin-set active/account fields are treated as eligibility without gateway account verification in the inspected path.

Fix: separate linked-account eligibility from selected settlement mode, reconcile account status from trusted gateway evidence, and test both transitions.

### F15 — Critical: organizer seat APIs do not enforce ownership consistently

`Api/Organizer/SlotSeatController.php:462,486,501,521,589` finds/mutates caller slot/ticket/seat IDs without checking their event belongs to the authenticated organizer. The auth route group checks role, not row ownership. Examples: drag/drop, delete, retrieve seats and update seat entries by arbitrary IDs. By contrast, sampled organizer booking show/delete methods correctly scope organizer_id; that protection does not cover these seat methods.

Fix: scope every slot/seat/ticket lookup through organizer-owned events and validate relationships before mutations. Test two organizers across all endpoints.

### F16 — Critical: QR suffixes are not validated as issued tickets; scans race

`ScannerApi/OrganizerScannerController.php:83` and AdminScannerController.php:76 split booking_id on __, check booking/payment (and organizer ownership where applicable), then accept any previously unseen suffix. They do not match that suffix against issued variation unique IDs or a valid ticket ordinal. Admission uses a read/update JSON array without row lock/atomic uniqueness. Different concurrent scans can overwrite each other; duplicate concurrent scans can both return success. Status-change endpoints likewise accept arbitrary ticket IDs.

Fix: issued-ticket identifiers, event/date/ownership validation and transactional admission with unique ticket admission state. Test fabricated suffixes, repeat and simultaneous scans.

### F17 — High: scanner organizer dashboard can disclose another event's bookings

`ScannerApi/OrganizerScannerController.php:180–209` accepts query id into ids. The event-list query scopes organizer_id, but the subsequent bookings query uses event_id in ids without the organizer scope. Supplying another organizer's event ID can yield its booking/ticket information despite an empty event list.

Fix: derive ids exclusively from the authorized event query and scope every booking query.

### F18 — High: scanner client leaks login credentials in URLs and fails open

`source/scanner-app/lib/services/api_client.dart` puts username/password/device_name in URL query parameters for POST login, exposing them to URL logging. checkQrCode returns success for any 2xx response that fails JSON parsing or is empty. Organizer/scanner hosts are hardcoded production. Scanner admission therefore has both confidentiality and invalid-response risks.

Fix: form/JSON request body, strict authenticated response schema, explicit timeouts/session recovery, build-defined staging hosts and fail-closed admission UI.

### F19 — High: customer checkout still depends on disabled Firebase

Customer CheckoutProvider invokes CheckoutPreflightModel.ensureFcmReady before any booking path. Firebase is disabled by default and initialization is Android-only; permission denial/error leaves an empty token. FcmTokenService sets initialized before acquisition and does not reset it on failure. The backend token field is nullable; project/mobile instructions explicitly require notifications to be optional.

Fix: optional token acquisition, retryable setup, and verify checkout without Firebase, with denied notifications and on iOS.

### F20 — High: customer payment contracts still use inconsistent units/status/hosts

The apps have not adopted /api/v1/payments/razorpay/order or /verify. Existing CheckoutPaymentModel treats legacy backend paidAmount as major units and multiplies again; Paystack/PhonePe local contracts already multiply by 100. Razorpay's extra /100 masks double conversion under the current backend but is not a sound contract. Stripe forces USD after minor units were calculated for the original currency; PayPal can label a converted USD amount as a different supported currency.

WebCheckoutUtils accepts unpaid/unsuccessful substring matches and positive numeric values recursively as payment success. Several URLs in AppUrls for Stripe/Flutterwave ignore PGW_BASE_URL and continue to production even with staging build definitions. SDK/WebView success fields remain client-supplied completed status.

Fix: explicit server-owned amount_minor/currency/order contracts, exact gateway status schemas and centralized environment URLs. Reconcile the old customer report with new v1 code rather than assuming v1 repaired the app.

### F21 — High: native customer auth is incompatible with current validation/response

Api/CustomerController login/signup still requires legacy captcha when enabled; customer AuthServices sends no captcha token, while web forms use the new action-aware reCAPTCHA v3 service. Customer signup backend returns success:true; AuthProvider.signup looks for status:success, and AuthServices returns the JSON unchanged. A successful registration can therefore be shown as a failure.

Web Google login authenticates a browser customer session, not a native token exchange; the current app declares no Google auth SDK. Do not treat a web redirect/callback as native integration.

Fix: native auth contract/token exchange, appropriate captcha strategy, typed success/errors and signup regression coverage. Never loosen server protections solely to fit the old client.

### F22 — High: notification APIs trust caller identity

Public get-notifications/save-fcm-token routes accept supplied user_id/fcm_token. FcmTokenController queries token OR user_id; a missing user_id can also select null-ID guest rows. Store permits supplied associations. Customer fetch uses unauthenticated base headers.

Fix: authenticated owner-derived identity, appropriate guest capabilities, validated platform and cross-customer isolation.

### F23 — High: reset lifetime is not enforced

Api/CustomerController.reset_password_submit verifies a hashed OTP and deletes it on success but never checks created_at expiry or a targeted per-account attempt budget. Web CustomerController.update_password directly looks up a reset token and updates the password without expiration/consumption in that path. The default password broker expiry settings do not enforce themselves in these manual implementations. API generic throttling is not an account-level reset policy; password reset does not revoke existing sessions/tokens in the inspected paths.

Fix: a shared expiring, single-use reset flow, targeted attempt controls, token revocation policy and valid/expired/replayed tests.

### F24 — High: uploads and support replies have unsafe boundaries

Legacy event-booking moves an arbitrary attachment into a public directory using its client extension, without MIME/content/size validation in that route. Without explicit server execution restrictions, executable uploads are a serious deployment risk; server execution configuration was not verified.

Customer SupportTicketController.reply finds any ticket ID and creates a conversation before checking customer ownership later. A foreign ticket can receive the inserted reply and the later null-owner update can fail after insertion. Organizer support lookups filter numeric user_id but omit user_type, enabling collision across customer/organizer ID spaces. Private invoices/support attachments are linked under public paths.

Fix: owner/type validation before any write, upload allowlists/content/size checks, and authenticated private delivery. Review all inherited upload helpers and cPanel directory rules.

### F25 — High: Mobile Homepage Studio is not connected to app delivery

Repository search finds MobileHomeResolver only in its class definition; no API/web consumer invokes active(). The live English home API exposes legacy secInfo/heroInfo/category/event fields, with no campaign/template/version payload. Flutter source has no campaign resolver integration.

Studio publish snapshots do not isolate edits: update mutates a published campaign/sections while resolver reads mutable current rows, so future integration would expose saved draft edits without publishing. Update does not invalidate cache; publish/toggle do. No rollback route consumes stored versions. Concurrent publish computes max(version)+1 without explicit campaign locking.

Fix: immutable published version selection, actual API contract and Flutter renderer, consistent cache invalidation, safe publish concurrency and restore workflow. Admin UI existence is not completed mobile functionality.

### F26 — Medium: release/toolchain and auth lifecycle need work

All three Android release configs still use debug signing. Customer manifest allows cleartext globally. Customer/scanner bearer storage uses SharedPreferences; customer AuthServices.token is only assigned in BookingCreateService and not synchronized by login/logout, so shared headers can be absent/stale. Several raw HTTP flows lack timeouts. Verify fresh-checkout branding assets separately because Git intentionally excludes logos/icons; current local presence does not establish clone build readiness.

Composer requires PHP ^8.3 while the lockfile contains Laravel 9.52.21. Laravel 9 security support ended 6 February 2024 according to [Laravel's release policy](https://raw.githubusercontent.com/laravel/docs/9.x/releases.md). No full dependency vulnerability scan ran. Preserve retired installer-provider changes before dependency reinstalls; do a staged framework/dependency upgrade with regression tests.

### F27 — High: test names exceed actual behavior verified

Website tests are example assertions; customer test is arithmetic, organizer test is empty, scanner test still expects a starter counter. DevTools auth checks mostly load forms. Razorpay mutation test only asserts a configuration flag; it does not create/pay/verify a transaction. The staging event test checks form controls, without authenticating storage state or submitting an event. Scanner contract checks validation/anonymous denial, not issued-ticket correctness or concurrency.

Automatic workflow uses live production routes, so passing browser checks cannot establish the changed source passed PHP/database tests. Workflow dispatch hardcodes mutation flags false and does not configure credentials; many core journeys are skipped. New payment controllers inject RazorpayRouteService before request validation, and its constructor queries gateway configuration; missing/misconfigured gateway setup can fail before an expected 422 contract response.

Fix: staged source-backed PHP integration tests, test-mode gateway fixtures, real cross-app journey, isolation and concurrent reservation/admission tests. Keep existing smoke tests but report skipped coverage explicitly.

## Compatibility matrix

| Area | Current app/backend evidence | Status |
|---|---|---|
| Discovery | Customer legacy GET API; live en responses 200, wildcard/unknown 500 | Conditional failure; locale fix needed |
| Customer signup | success:true versus status:success; captcha token omitted | Static mismatch |
| Google native auth | Web session callback exists; no native exchange integrated | Not integrated |
| Customer private booking details | Sampled API scopes customer_id and booking id | Positive source check; authenticated tests pending |
| Customer payments | App uses legacy event-booking/verify-payment, not v1 orders | Not integrated; payment bypass remains |
| v1 Razorpay | Server pricing/signature/amount checks exist | Partial; F05–F14 block release |
| Free/offline/seat/coupon bookings | Legacy client features; new order path lacks complete support | Contract reconciliation required |
| Ticket delivery | New finalizer has no invoice/mail job dispatch | Missing in inspected path |
| Organizer bookings | Sampled show/delete scopes owner | Positive source check; other endpoints differ |
| Organizer payment settings | v1 exists but Flutter URLs/services do not use it | Not integrated; transition logic faulty |
| Scanner | Correct /api/scanner role paths used by app | Ticket validation/concurrency/isolation failures |
| Homepage campaigns | Admin studio/tables exist; resolver unused; no API payload | Not delivered to app |
| Notifications | Disabled owned Firebase plus mandatory customer preflight | Checkout blocker |
| Release | Debug signing and placeholder tests | Not ready |

## What was verified

- Fetch/branch/remote/working-tree history inspected; no incoming main changes and prior report preserved.
- Node v24.19.0 was found in the Codex runtime cache, although node was not on PATH.
- `node --check` passed for 26 DevTools source/test/config JavaScript files.
- Eight direct local assertions against the real mutation guard passed: production denied with mutation flag on/off, staging allowed only when explicitly enabled, HTTP/external/malformed targets denied. No network writes were made.
- Twelve Android/iOS XML manifests parsed successfully; internal Booktkit package import target checks under all three lib directories found zero missing files. These are static checks, not Flutter compilation. The audit evidence JSON was parsed and its reviewed SHA/120-file inventory checked.
- Read-only live GET checks: website / and get-basic returned 200; customer discovery with en returned 200; wildcard/unknown returned 500; anonymous organizer payment-settings/scanner events returned 401. Only status/schema keys were printed, not credentials/customer data.
- `git diff --check 05e3f5a..HEAD` reports two pre-existing trailing-whitespace lines in frontend/customer/dashboard/index.blade.php. Those application lines were not changed by this audit.

PHP, Composer, Flutter and Dart were not found on this session's PATH or in the inspected cached runtimes. No PHP lint/test, Composer audit, migrations, Flutter analyze/test/build, browser rendering, emulator, authenticated role/isolation test, payment/refund/admission mutation or full Playwright suite ran. No deployment occurred. The live commit/database migrations, queues, mail configuration, backups, and hosting private-file/execution protections remain unknown. Public route presence does not prove production matches the reviewed SHA.

## Instruction reconciliation

The root mobile guidance correctly requires latest shared backend review, optional notifications and staging-only state changes. Those requirements are not yet satisfied by the app implementations. Project instructions still cite the 3 October cleanup deployment and known older issues; that deployment note does not identify the current production commit. Source README statements about no live changes are historical and must not override the later deployment record. The old customer report remains useful for legacy paths but predates new v1 services; use this audit for current combined risk.

The user's Git policy was followed. No automatic merge, force push, source fix or main commit was performed. Audit documents are committed separately on the feature branch; committing/pushing those documents is not a production deployment.

## Remediation order and acceptance gates

1. Close public secret/model-field/payment bypasses and organizer/customer data isolation gaps, preserving compatible validated guest/free/offline behavior. Establish deployed version first.
2. Repair v1 ownership, immutable idempotent orders, capture confirmation, atomic reservations, webhook/retry fulfillment, settlement outbox, accounting and delivery. Gate with PHP/database tests and gateway test mode.
3. Fix QR issued-ticket validation and atomic admission; test fake/duplicate/concurrent scans and another organizer's event.
4. Integrate customer auth/checkout with real contracts, remove notification gating, centralize all staging URLs, then integrate organizer/scanner updates. Verify discovery → purchase → ticket → organizer visibility → admission on one staging fixture.
5. Connect homepage publishing to a versioned API/rendering flow, validate drafts/disabled/scheduled campaigns, fix locale fallback, and run browser/device QA.
6. Upgrade supported dependencies in staging, retain supplier retirement, configure owned services/signing, and validate backups/rollback/queues/private files before an explicitly authorized production release.
