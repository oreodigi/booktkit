# BookTKIT Mobile API Contract Baseline

Reconciled from canonical main on 6 October 2026. This is a moving integration baseline; controllers/requests are authoritative for exact JSON.

## Routing
Laravel API and scanner route groups are mounted under `/api`. Keep customer, organizer, admin and staff guards distinct.

## Core domains
- Discovery/customer APIs: events/categories/details and customer private data.
- Payments V2: server Razorpay order/verify, organizer payment settings, webhook server-to-server.
- Organizer management: organizer Sanctum-scoped event/ticket/booking operations.
- Scanner/Access: organizer/admin/staff scanner paths, authorized event/gate discovery and admission.
- Passes: current web/POS/backend support pass products/date entitlements; mobile endpoints must be verified before client implementation.

## Payment contract
Server creates gateway orders from event/items/customer/idempotency data and returns authoritative amount/currency/breakdown. Amounts are gateway minor units. SDK success is only input to server verification.

Current finalization preserves authenticated customer identity and pass selections. Free pass flows also issue entitlements/consume stock. Never infer settlement or fees on-device.

## Access/scanner contract
Current admission architecture is no longer a one-way booking scan.

Scanner requests must use the current role-specific authenticated API and provide the context required by the endpoint, including explicit `entry` or `exit` direction and selected gate where configured. Staff scanner sessions are organizer/assignment scoped.

Server resolves a secure BookTKIT ticket token or active credential to an `IssuedTicket`, then validates event, actor assignment/permission, gate/zone, credential status, presence/re-entry state and pass/date entitlement transactionally.

Expected denial classes include invalid/revoked/replaced credential, wrong event/gate, unauthorized staff, already inside/outside, re-entry exhausted and pass date mismatch. Supervisor override is privileged and audited.

Legacy manual scanned-status mutation must not be used by new clients as admission authority.

## Known integration rule
Backend feature existence is not proof the Flutter apps already implement it. Inspect each app URL/client/DTO before changing it and prefer additive compatible APIs. Customer/organizer/scanner environment URLs should be configurable for staging.

## Acceptance
On the same isolated staging dataset: create/obtain authorized accounts, buy/free-book a supported event/pass, verify issued ticket/customer ownership, verify organizer visibility, assign credential when required, discover authorized gate, perform entry/exit/re-entry and prove duplicate/cross-organizer/revoked/pass-date-invalid attempts fail.
