# Booktkit project review

Reviewed on 3 October 2026. This is a static review of the supplied archives, focused on the remaining mobile apps, their backend integration, and launch risks. No production files or database records were changed.

## Overall assessment

The package contains an existing multivendor event-ticketing platform called Evento. It provides substantial functionality, but the supplied mobile apps are still configured for the supplier's service and are not ready to publish as Booktkit. Several backend security and correctness issues should be resolved before connecting those apps to production.

Your statement that the website is live is accepted. An attempted public fetch of https://booktkit.com failed through the browsing tool; this does not establish that the website is down. The cPanel configuration, live source, database, payment credentials, and deployed version were unavailable for comparison.

## What is in the folder

| Component | Technology / purpose | Supplied state |
|---|---|---|
| Website | Laravel backend, website, admin and organizer panels, API, MySQL configuration | installable.zip, documentation, and updater_4.2_to_5.0.zip |
| Customer app | Flutter; event discovery, booking, payments, account, support, notifications | Source archive; supplier API defaults, Evento branding, sample Android ID |
| Organizer app | Flutter; event/ticket management, bookings, income, withdrawals, support, seating | Source archive; supplier API URL and branding |
| Scanner app | Flutter; admin/organizer login, QR admission, scan history, attendee lists | Source archive; supplier API URL and branding |
| Brand assets | Book tkit logos folder and ZIP | Available separately; integration into apps not verified |

The website lockfile specifies Laravel **9.52.21**, while its documentation says 9.52.7. composer.json requires PHP **^8.3**. The website documentation or supplier package label should not be used as proof of the deployed version.

The app source constraints are Dart ^3.9.0 (customer), ^3.10.7 (organizer), and ^3.9.2 (scanner). The generic documentation's minimum Flutter version alone is insufficient: the installed Flutter SDK must include Dart satisfying the actual pubspec constraints.

Relevant source files were extracted into `project-review/source` for inspection and reference. This is a partial inspection copy, excluding most dependencies, website assets, and other archive content; it is not a complete runnable installation. Original archives are intact.

## Priority findings

### 1. Critical: booking creation trusts caller payment status and prices

Evidence: `source/website/routes/api.php`, public POST `/api/event-booking`; `source/website/app/Http/Controllers/Api/EventController.php:853`, especially lines 896–947 and `storeData` at 1025.

The endpoint accepts `total`, tax, discounts, quantity, customer_id, and paymentStatus from the request. When paymentStatus is omitted, gatewayType=online defaults to completed. It persists the booking and can issue tickets and credit organizer revenue without a gateway verification step in that flow. The separate `verifyPayment` method at 1695 only checks currency and converts amounts; it does not verify a settled transaction.

Required change: compute the price from server ticket data, authenticate customer ownership or explicitly support guests, create a pending booking, and complete it only after server-side verification of payment amount, currency, order and gateway transaction. Make completion idempotent so callbacks cannot credit revenue twice. A mobile UI's payment-success callback is insufficient protection for this public endpoint.

This is confirmed in the supplied source; production exposure depends on whether the live code matches it. No exploit was attempted.

### 2. Critical: unrestricted public attachment upload

Evidence: `source/website/app/Http/Controllers/Api/EventController.php:889`.

An offline booking attachment is moved into a public folder using its original extension, with no attachment type or size validation in this method. If the deployed server executes uploaded script extensions there, this could allow code execution. Even with execution disabled, unrestricted uploads create abuse and storage risks.

Required change: allowlist appropriate document/image types, validate content and size, use random filenames, store privately, and serve through a controlled download route. The server's upload-directory execution policy was not inspected.

### 3. High: organizer scanner can disclose another organizer's tickets

Evidence: `source/website/app/Http/Controllers/ScannerApi/OrganizerScannerController.php:181`.

When an organizer supplies an event id, the event listing applies organizer ownership, but the subsequent Booking::whereIn('event_id', $ids) query does not. The response builds ticket records containing booking identifiers, ticket identifiers and customer phone numbers. Consequently the supplied code permits an authenticated organizer to request ticket data for an event they do not own.

Required change: validate the requested event against the authenticated organizer before using its id, and scope every booking query to that owner.

### 4. High: scanner accepts unissued ticket suffixes

Evidence: `source/website/app/Http/Controllers/ScannerApi/OrganizerScannerController.php:82`; the older API scanner controller also contains this pattern.

The QR check splits a booking identifier at `__`, checks booking ownership/payment, and accepts any suffix not already recorded as scanned. It does not validate that the suffix belongs to an issued variation ticket or is within the purchased quantity. The read/append/save operation also lacks an atomic concurrency guard, allowing simultaneous scans to pass.

Required change: resolve a real issued ticket, validate event/date and eligibility, and atomically record admission. Verify behavior across both admin and organizer scanner implementations.

### 5. High: notifications are retrieved using a caller-supplied user id

Evidence: `source/website/routes/api.php`; `source/website/app/Http/Controllers/Api/FcmTokenController.php`.

GET `/api/get-notifications` is outside the authenticated customer group and queries by supplied user_id or token. POST `/api/save-fcm-token` also accepts user_id from the caller. This can disclose notification titles, descriptions and booking references, or associate tokens with other users.

Required change: derive user identity from authentication. Keep any guest notification access separate and bound to a securely established device/session identity.

### 6. High: inventory is not enforced atomically at booking creation

Evidence: `source/website/app/Http/Controllers/Api/EventController.php:1025`.

The routine modifies stock and then creates a booking without a surrounding transaction or row lock. For limited normal/free tickets, insufficient inventory skips the decrement but does not reject the booking. Other branches can reduce inventory below zero. Ticket selection is looked up by ticket id without checking that each selected ticket belongs to the requested event.

Required change: validate ticket/event/seat ownership, quantities and availability at final submission, then reserve inventory and create the booking in one transaction with concurrency protection. An earlier checkout check cannot prevent simultaneous purchases.

### 7. Framework maintenance is overdue

Laravel 9 security support ended on 6 February 2024, according to the [official Laravel support table](https://laravel.com/framework/docs/10.x/releases). The package remains on Laravel 9.52.21. Plan a tested upgrade to a supported release, including payment packages and PHP compatibility. This review did not perform a dependency vulnerability audit or establish any specific dependency CVE.

### 8. Additional correctness and deployment concerns

- Password reset checks the code hash but does not check the reset record's age (`CustomerController.php:234`). Add expiry and targeted attempt limits; the shared API limiter is not a substitute for per-account reset controls.
- Booking zip_code is populated from city (`EventController.php:927`).
- API and scanner route files repeat several route names. Check route caching and URL generation for collisions before enabling production caches.
- Cron operations are exposed as GET `/check-payment`, `/send-ticket`, and `/send-push-notification-phone` in routes/web.php. Confirm access restrictions and concurrency controls. The Laravel scheduler method is empty, so adding schedule:run alone does not configure these jobs.
- Some API methods dereference a language lookup without a fallback when an unsupported Accept-Language value is supplied. This can cause API errors.
- The packaged website tests contain only the default homepage-status test and assertTrue example. They provide no evidence that payments, stock, owner isolation or QR admission work correctly.

## Remaining mobile-app work

### Connect all three apps to Booktkit

- Customer: `source/customer/lib/app/urls.dart` defaults to `https://php82.kreativdev.com/evento/api`, with a separate `/pgw` payment base. It supports API_BASE_URL and PGW_BASE_URL build overrides. Configure both, not just the API.
- Organizer: change the base in `source/organizer/lib/app/urls.dart`; existing relative routes largely correspond to the supplied backend.
- Scanner: change the base in `source/scanner/lib/services/api_client.dart`; this app uses the backend's `/api/scanner/...` endpoints.

For a root installation, the likely API URL is `https://booktkit.com/api` and payment base `https://booktkit.com/pgw`. These are candidate values until the deployed routes and payment scripts are verified. The website archive includes standalone payment scripts under public/pgw; their provider behavior and deployment were not audited here.

### Branding and release configuration

All three Android manifests still show Evento names. Customer and scanner use com.example application IDs; organizer uses the supplier's com.eventoorg ID. All three release build configurations currently sign with debug keys. Set owned, distinct package/bundle identifiers; apply Booktkit names, logos, splash screens, colors and store assets; configure private production signing and backup the signing material. Check iOS identifiers, capabilities and signing separately.

### Firebase and notifications

The customer app's firebase_options.dart points to supplier project `evento-4df74`. Configure an owned Firebase project and regenerate platform configuration against the final application identifiers. Match backend service-account configuration and test token registration, foreground/background notifications and notification navigation. Public Firebase client configuration is not itself a server credential.

### Verification before publishing

Test signup/login/password reset; discovery and language fallback; successful, failed and cancelled payments; duplicate payment callbacks; offline/free bookings; last-ticket concurrent booking; ticket delivery; two organizers' access isolation; organizer creation/editing and withdrawals; legitimate/invalid/repeated/simultaneous QR scans; notifications; logout and expired sessions. Perform state-changing tests against a staging database and payment test environment.

## cPanel checks still required

Confirm deployed version/source, PHP and extensions, APP_URL, production debug settings, public document root or equivalent protection of private application files, installer removal/restriction, HTTPS, email delivery, storage permissions, cron entries, queue execution when asynchronous jobs are enabled, payment callbacks, backup restoration and logs. These are unverified checks, not assertions that your cPanel setup is wrong.

The provided updater is specifically labelled 4.2 to 5.0. Do not apply it merely because it exists: establish the current installed version, supplier instructions, custom changes and database backup first.

## Recommended completion order

1. Compare the flagged backend methods with live cPanel code and prepare a staging copy.
2. Fix booking/payment trust, uploads, owner isolation, scanner validation and stock concurrency.
3. Configure Booktkit API/payment URLs, identities, branding and Firebase for the apps.
4. Test the customer booking-to-ticket-to-scanner flow and organizer management end to end.
5. Configure production signing and publish validated mobile builds.
6. Complete the framework upgrade and repeat payment/integration regression checks.

## Review limits

PHP, Composer, Flutter and Dart were not available through the current command path; no app build or backend tests were run. There was no access to production source/database or cPanel. This review covers selected application source, routes, configuration, dependencies and documentation, not every asset, third-party library, standalone payment script or update migration. Findings should be reconciled against your deployed code before production changes.
