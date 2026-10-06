# BookTKIT Box Office & POS — Current Design

Last reconciled with main: 6 October 2026.

## Product
`box_office` is a first-class event type across event navigation/filtering/ticket management. POS is the operational sales channel/workspace and can sell supported ticket/pass products for authorized events.

## POS workspace
Current organizer POS is a three-column selling interface with products/event context, active cart/customer details and persistent cart actions including Hold and Pay. It is not a simple booking form.

Backend behavior includes:
- server-authoritative quote/pricing and locked inventory;
- idempotent organizer-scoped sales;
- ticket variations and pass products;
- customer and optional identity metadata with validated uploads;
- issued QR tickets, thermal print view and optional email delivery;
- hold/resume operations;
- cash/card/UPI/manual channel metadata as supported by configured flow;
- staff assignment-scoped POS;
- cash shifts and organizer verification;
- Box Office reports;
- organizer/admin POS settings;
- audit/history preservation when staff identities are archived.

## Workforce
Organizer Team supports operational staff profiles, departments/roles, assignments and permissions. Admin can oversee organizer workforce. Staff POS/scanner access is permission- and assignment-scoped; staff are not organizer superusers.

## Inventory and concurrency
Inventory reservation/sale must use transactional locking. Variation inventory is atomic. Pass stock/entitlements are consumed server-side. Client cart quantities are requests, not authority.

## Payments and fees
POS fees are resolved through Payments V2 as a sales-channel fee and can coexist with event-type/platform/additional fees. Do not hardcode POS fees in the POS UI. The organizer payout/KYC banner does not belong in the selling workspace.

## Tickets and Access
Successful POS sales issue real `IssuedTicket` records. For credential-enabled events, POS can continue into QR/RFID credential assignment. Credential issuance is a separate recoverable operation and must not invalidate a completed financial sale.

## Staff cash shifts
Where staff cash-shift policy is enabled, staff must have an open shift before cash-sensitive POS operation. Sales retain shift/staff linkage for reconciliation; deletion/archive of staff must not destroy historical records.

## Security
Enforce organizer ownership, staff assignment, permission, idempotency and upload validation server-side. Never trust client totals/payment status. Keep financial sale, ticket issuance and credential assignment auditable and recoverable.
