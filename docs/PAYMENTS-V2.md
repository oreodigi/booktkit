# BookTKIT Payments V2 — Canonical Payment Flow

Status: fee engine, settlement routing/fallback and organizer/admin payment surfaces are implemented on canonical main as of 6 October 2026; continue to verify production migration/runtime separately.

## Authority
BookTKIT uses one server-authoritative payment architecture. The backend owns ticket/pass pricing, discounts, taxes, platform/POS/additional fees, currency, gateway orders, verification, settlement mode, booking finalization, ticket issuance, ledger and reconciliation.

## Settlement modes
### BookTKIT Managed
BookTKIT collects the payment and records organizer payable for later settlement. This is the safe/default fallback.

### Razorpay Direct / Route
BookTKIT still owns checkout and fee calculation; eligible organizer payable is routed/transferred through the linked-account architecture.

Direct is allowed only when canonical linked-account/KYC/split eligibility passes. Pending/rejected/suspended/unavailable eligibility automatically falls back to BookTKIT Managed for new sales. Ticket sales must not be blocked solely because organizer Razorpay verification is incomplete.

Each payment order snapshots the actual mode and reason; later settings changes do not rewrite history.

## Fee engine
Resolve the most specific active rule: event override -> organizer override -> event-type + sales-channel -> global default, according to current resolver implementation.

Event types include online, venue and box office. Sales channels include web/mobile/POS/admin-manual where supported.

Fees can include percentage/fixed/hybrid platform fees, POS fees and reusable additional fees such as wristband/RFID/pass/parking/delivery/convenience. Customer-facing fee lines and accounting snapshots are immutable per order.

## Passes and checkout
Current payment/finalization path preserves pass selections and authoritative pass pricing through Razorpay finalization. Free pass bookings also issue entitlements and consume pass stock. Invalid pass quantities/dates must fail server-side.

## Organizer UX
Organizer Payments exposes settlement preference/effective mode, Razorpay/KYC state, fallback reason and fee schedule. Fee cards are transparent to the organizer. Payment/settlement routes live under the organizer namespace; legacy/dead links should not be revived.

## POS
POS is a sales channel. POS fee configuration is independent of payout KYC UX; payout setup banners must not interrupt the POS workspace.

## Ledger/refunds
Ledger/reconciliation remains accounting authority. Refunds, reversals and transfers are idempotent and must operate from immutable order snapshots rather than current fee configuration.

## Security
Never accept client totals, settlement choice or payment-success flags as authority. Payment finalization/webhooks/ticket issuance must be idempotent. Organizer ownership and idempotency scope are server-enforced. Use staging/test gateway for state-changing development.
