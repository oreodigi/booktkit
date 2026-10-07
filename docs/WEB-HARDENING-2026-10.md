# Web hardening — October 2026

Branch `fix/web-hardening-2026-10`. Web/backend only; Flutter apps unchanged. Register IDs refer to
`docs/BOOKTKIT-FUNCTIONAL-SPECIFICATION.md` §14.

## New services and commands
| Item | Purpose |
|---|---|
| `Services/Payments/PaymentCaptureService` | Single idempotent capture → booking path (web callback, API verify, webhook, recovery) |
| `php artisan payments:recover-unfinalized` | Retries captured payments without a booking; optional auto-refund (`BOOKTKIT_AUTO_REFUND_UNFULFILLED`) — scheduled every 10 min |
| `php artisan tickets:secure-public-files` | One-off: renames existing ticket PDFs to unguessable names, deletes public QR images |
| `Services/Payments/LegacyAppBookingVerifier` | Server decision for `POST /api/event-booking` (free / Razorpay-verified / pending) |
| `Services/Payments/CouponService` | Server-side coupon pricing for Razorpay orders |
| `Services/Events/CommercialRecordGuard`, `EventDeletionService` | Ownership and never-delete-sales rules |
| `Services/Bookings/BookingStatusService` | Manual offline approval rules; issued tickets/credentials follow booking status |
| `Services/BoxOffice/BoxOfficeSaleInput`, `BoxOfficeVoidService` | One POS input contract driven by settings; full void reversal |
| `Http/Controllers/Concerns/AdmitsThroughUnifiedEngine` | Legacy scanner URLs adapted to the admission engine |
| `Support/BusinessTime` | Event date checks in `BOOKTKIT_TIMEZONE` (default Asia/Kolkata) |

## Migrations (additive)
- `2026_10_07_090000_add_finalization_error_to_payment_orders`
- `2026_10_07_090100_add_gateway_payment_id_to_bookings`

## Config (`config/booktkit.php`)
`CRON_HTTP_TOKEN`, `BOOKTKIT_CONFIRMATION_ACCESS_MINUTES`, `BOOKTKIT_AUTO_REFUND_UNFULFILLED`,
`BOOKTKIT_UNFULFILLED_REFUND_AFTER_MINUTES`, `BOOKTKIT_ADMISSION_GRACE_HOURS`, `BOOKTKIT_TIMEZONE`.

## Server
Add `* * * * * php artisan schedule:run` for each target. Production needs `deploy.py --target production --migrate`.

## Tests
`tests/Feature/Hardening/*` — HTTP and service-level behaviour tests against `booktkit_test`.
Note: `AppServiceProvider` shares `websiteInfo` only outside the console, so tests share it in
`BuildsFixtures::seedPlatform()` and assert 404s with JSON requests.
