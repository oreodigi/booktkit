# Mobile API integration baseline
Verified statically from GitHub main on 5 October 2026. No live requests or app builds were performed. Re-read code before implementation; this inventory is not a stable API specification.

## Sources of truth
source/website/routes/api.php, routes/scanner_api.php, app/Providers/RouteServiceProvider.php, API controllers, requests, services and migrations.
Both API route files are mounted at /api. Inspect controllers for exact response/auth schemas rather than guessing from route names.

| Area | Observed routes | Integration requirement |
|---|---|---|
| Discovery | GET /api/, /api/get-basic, /api/events, /api/events/details, /api/events/categories | Map actual JSON and media URLs; verify configured homepage sections are exposed |
| Customer auth | POST /api/customer/login/submit, /api/customer/signup/submit; Google redirect/callback under /api/customer | Verify native token exchange, reCAPTCHA actions and current validation |
| Customer private data | /api/customers/* | auth:sanctum; derive ownership on server |
| Organizer management | /api/organizer/* | Management group uses auth:organizer_sanctum; login points to OrganizerScannerController in inspected routes; inspect behavior |
| Scanner | /api/scanner/organizer/* and /api/scanner/admin/* | Separate guards organizer_sanctum/admin_sanctum; do not substitute legacy /api/organizer/check-qrcode blindly |
| Payment creation | POST /api/v1/payments/razorpay/order | Server price and order; see request below |
| Payment verification | POST /api/v1/payments/razorpay/verify | Verify server response; SDK success is only input to verification |
| Organizer payment settings | GET/PUT /api/v1/organizer/payments/settings | auth:organizer_sanctum; inspect settings schema and eligibility |
| Razorpay webhook | POST /api/v1/webhooks/razorpay | Server-to-server only; never expose webhook credentials in apps |

## Observed Razorpay contract
PaymentController::createRazorpayOrder validates event_id; items array with ticket_id, quantity (1..50), optional variation; required idempotency_key; and customer containing fname, lname, email, phone, country, address. Optional state, city, zip_code, customer_id, event_date, fcm_token.
Response: success, payment_order UUID, gateway_order_id, amount, currency, breakdown (ticket_amount, platform_fee, tax, organizer_amount, fee_bearer).
PaymentOrderService currently fixes currency INR; amounts are integer paise. Display once as rupees; pass amount directly as Razorpay minor units, without another *100.
Verification request: payment_order UUID, razorpay_payment_id, razorpay_signature. Response may include status paid and booking_id; already-paid response currently returns idempotent=true without booking_id. Implement/reconcile reliable booking retrieval and recovery; do not invent a status endpoint.
Trace PaymentOrderService, AuthoritativeTicketPricingService, RazorpayRouteService, BookingFinalizationService and webhook code before adapting checkout.
Observed routes do not explicitly authenticate the payment group. Verify ownership, idempotency scoping, order access and server verification before release; do not treat public routing as authorization to accept arbitrary customer_id.
The pricing service inspected handles normal/free/variation pricing and early-bird discounts. Do not assume coupon, seat reservation or arbitrary date-bundle support is present in this new order path. Reconcile capabilities with web checkout and extend safely where needed.

## Known mobile mismatch
customer-app/lib/app/urls.dart still maps paymentProcessUrl to /api/event-booking and uses legacy /api/event/verify-payment. New payment routes coexist with legacy routes; existence is not proof mobile integration is complete.
Customer overrides API_BASE_URL/PGW_BASE_URL exist, but several gateway helpers still use the hardcoded production host. Organizer/scanner configuration must be inspected and normalized.
Local project-review/CUSTOMER-APP-ANALYSIS.md flags notification-dependent checkout, permissive paid detection, token lifecycle, signing and amount units. Recheck against fetched latest code; fixes may have landed elsewhere.

## Cross-app acceptance
Use the same staging database/API and a fixture event. Customer buys a ticket, server verifies payment and issues booking, organizer sees that booking, scanner validates and admits the same ticket. Repeated/concurrent scans cannot double-admit. Cross-organizer access and forged payment/QR states must fail server-side.
Record exact JSON contracts with sanitized examples, status codes, auth requirements and automated regression tests in docs/mobile/COMPATIBILITY.md during implementation.
