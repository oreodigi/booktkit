# BookTKIT Payments V2 — Canonical Payment Flow

Status: implementation in progress

## Objectives
BookTKIT uses one server-authoritative payment system for every ticket sale. Pricing, fees, settlement routing, refunds, transfers, reconciliation and ticket issuance are backend decisions. Razorpay KYC must never block an organizer from selling tickets.

## Settlement modes
### BookTKIT Managed
BookTKIT collects the customer payment into the BookTKIT Razorpay account. Organizer payable is recorded in the ledger and settled under BookTKIT payout policy. This mode is always available and is the automatic fallback whenever Razorpay Direct is unavailable.

### Razorpay Direct
The organizer prefers automated settlement through the existing Razorpay Route linked-account architecture. BookTKIT remains checkout/payment-order authority and calculates all fees. Organizer funds are transferred only when the linked account passes canonical eligibility checks: active Razorpay account/product, activated KYC, split enabled and not suspended.

If eligibility is lost, pending, rejected, suspended or unavailable, new sales automatically use BookTKIT Managed. Existing orders keep the settlement mode snapshotted when created.

## Settlement decision
1. Load organizer payment preference.
2. Resolve event and sales channel.
3. If preference is Razorpay Direct, verify current Route/KYC eligibility.
4. If eligible use razorpay_split; otherwise use booktkit_managed.
5. Persist actual mode and decision reason in the order snapshot.
6. Never mutate historical order settlement/fee snapshots because settings changed.

## Fee hierarchy
Resolve the most-specific active rule: specific event override, organizer override, event-type + sales-channel rule, then global default.

Supported event types include venue, online and box-office. Sales channels include web, mobile, POS and admin/manual where applicable. Rules support percentage, fixed, hybrid, minimum/maximum fee and fee bearer. Commercial values are admin-configurable, not hardcoded.

## POS
POS is a sales channel, not an event type. POS can have a BookTKIT fee independent of the underlying event type, including percentage, per-order fixed and optional per-ticket fixed fees.

## Additional fees
Additional charges use a reusable fee model rather than a hardcoded wristband column. Examples include wristband, RFID/pass, parking, delivery and venue convenience fees. Fees can define scope, fixed/percentage calculation, per-order/per-ticket application, mandatory/optional state and bearer. Checkout/POS show customer-facing charges explicitly and the payment order snapshots every fee line.

## Immutable order accounting
Every order snapshots ticket subtotal, discounts, tax, platform fee, POS fee, additional fee lines, customer total, organizer payable, BookTKIT revenue, settlement mode, fee-rule metadata, sales channel and event type. Later configuration changes affect only new orders.

## Ledger and refunds
The payment ledger is the accounting source of truth. Ticket value, platform revenue, additional fees, organizer payable, refunds and transfer/reversal activity remain separately identifiable. Refunds/reversals are idempotent and operate against immutable orders.

## Admin Payments & Finance
Canonical navigation: Overview, Transactions, Settlements, Organizers, Fee Rules, POS Fees, Additional Fees, Refunds, Reconciliation, Razorpay, Reports and Settings. Legacy/dead payment links are audited and either redirected to canonical destinations or removed when proven unused.

## Organizer UX
Organizer Payments exposes preferred settlement method, Razorpay/KYC status, effective mode, fallback reason, gross sales, fees, refunds, BookTKIT-held balance, routed amount, pending settlement and settled amount. Selecting Razorpay Direct while verification is incomplete never disables ticket sales.

## Security invariants
- Server owns price, fee, tax, currency, payment and settlement decisions.
- Payment finalization, webhooks and ticket issuance are idempotent.
- Organizer ownership is server-enforced.
- Payment history is never recomputed from current settings.
- Production secrets never enter source control.
- State-changing payment development uses test/staging mode before production release.

## Implementation phases
1. Canonical documentation and schema for fee rules/additional fee lines/channel snapshots.
2. Fee resolution engine and settlement routing policy.
3. Admin Fee Rules/POS/Additional Fees management.
4. Organizer settlement preference/KYC fallback UX.
5. Web/mobile/POS integration with the same authoritative order service.
6. Legacy payment-route cleanup, reconciliation, reports and production migration/verification.
