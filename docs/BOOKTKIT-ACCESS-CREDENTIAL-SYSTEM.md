# BookTKIT Access & Credential System — Architecture and Integration Audit

**Status:** Architecture/audit approved for phased implementation; no application/schema implementation in this document commit.  
**Repository audited:** `oreodigi/booktkit` current `main` on 6 October 2026.  
**Purpose:** Safely add physical/digital event credentials (QR wristbands, RFID/NFC cards, QR badges and physical ID cards), controlled issue/replacement, multi-entry/exit access control, zones and auditable venue operations without weakening BookTKIT's existing ticket, payment, POS or scanner authority.

## 1. Product model

A BookTKIT **ticket is the commercial entitlement to attend**. A **credential is the physical/digital object used to prove that entitlement at a venue**.

Do not replace `issued_tickets` with wristbands and do not encode customer PII into printed QR/RFID identifiers.

Target relationship:

```
Booking -> IssuedTicket -> TicketCredential -> Credential
                              |
                              +-> assignment/replacement history

Credential + IssuedTicket + Event Access Policy + Gate
                              |
                              v
                       Admission Engine
                              |
                    Access Scan / Attendance State
```

Supported credential types should be extensible:
- `qr_wristband`
- `rfid_wristband`
- `rfid_card`
- `nfc_card`
- `qr_badge`
- `physical_id`

A credential identifier must be a cryptographically strong opaque token/UID. Store a hash for QR bearer tokens where practical. Never print sequential database IDs, booking IDs, customer details or authorization policy into the credential.

## 2. Required workflows

### Online purchase and collection
1. Existing payment flow finalizes a paid/free booking.
2. Existing `TicketIssuanceService` creates one authoritative `IssuedTicket` per attendee ticket.
3. Customer ticket/PDF indicates **Credential collection required** when the event policy requires it.
4. At the collection desk, authorized staff scans the customer's secure BookTKIT ticket QR.
5. Staff scans an unassigned physical credential.
6. Server atomically verifies event/organizer/ticket/credential/state and assigns + activates the credential.
7. From that point the physical credential can be the primary gate token. The original ticket remains the entitlement and recovery proof, not a second independent admission identity.

### Box Office / POS sale
1. Existing `BoxOfficeSaleService` creates booking, reserves inventory and issues `IssuedTicket`.
2. If the event requires credentials, POS offers **Issue credential** immediately after successful sale.
3. Staff scans the physical band/card and the server binds it to the selected issued ticket.
4. Credential assignment must never be required to commit the financial sale; a failed printer/scanner/credential operation must be recoverable from an Issue/Collection queue.

### Entry and exit
Admission is a server-owned state machine:

```
outside --ENTRY--> inside --EXIT--> outside --RE-ENTRY--> inside
```

Initial entry is not a re-entry. Event policy may be:
- no re-entry
- unlimited re-entry
- limited re-entry with `max_reentries`

Exit scanning is required when re-entry is enabled. An ENTRY against an already-inside ticket is denied. An EXIT against an already-outside ticket is denied.

### Lost/damaged credential
Never delete the old credential.
1. Authorized staff opens replacement workflow.
2. Verify attendee/ticket.
3. Capture reason: lost, damaged, unreadable, RFID failure, staff replacement, other.
4. Collect configured replacement charge when applicable.
5. Atomically revoke old credential and activate the new credential.
6. Old credential must permanently fail admission.
7. Preserve replacement/payment/operator audit history.

## 3. Event configuration

Create a dedicated Access/Credentials policy instead of continuing to overload `box_office_enabled`.

Proposed policy fields:
- credential mode: ticket-only / credential-required / hybrid
- credential type(s)
- collection required
- allow initial ticket QR before credential assignment
- re-entry policy and maximum re-entries
- exit scan required
- replacement allowed
- replacement fee / fee reference
- maximum replacements
- identity verification mode
- activation policy
- validity window/date
- zone policy
- offline policy (future)

Existing `events.reentry_policy` and `events.max_reentries` can be migrated/reused as compatibility fields initially, but access behavior should ultimately be owned by a dedicated access-policy model. `box_office_enabled` is a sales-channel concern and must not remain the switch that decides whether re-entry exists.

## 4. Proposed domain tables

Final names may change after migration review, but keep responsibilities separate.

### `event_access_policies`
One policy per event initially. Holds credential/re-entry/replacement/identity settings.

### `credential_batches`
Inventory/import batch for preprinted wristbands/cards:
- event/organizer ownership
- credential type
- batch code
- supplier/reference metadata
- expected quantity
- status

### `credentials`
Physical credential identity/inventory:
- UUID/public reference
- batch/event/organizer
- type
- identifier hash / RFID UID representation
- lifecycle status: unassigned, assigned, active, revoked, lost, damaged, expired, void
- timestamps

No customer PII.

### `ticket_credentials`
Assignment history between `issued_tickets` and credentials. Enforce only one active credential per issued ticket and only one active ticket assignment per credential.

### `credential_replacements`
Immutable replacement event:
- issued ticket
- old credential
- new credential
- reason
- operator
- charge/payment reference
- timestamp

### `event_access_zones`
Optional in early UI but schema-ready for General, VIP, Backstage, Staff, Media, etc.

### `event_gates`
Gate/checkpoint definition with event, zone, mode and active status.

### `access_scans`
Append-only scan ledger:
- event, issued ticket, credential (nullable for ticket QR)
- gate/zone
- actor type/id and device
- requested action: entry / exit / checkpoint
- result: allowed / denied / overridden
- reason code
- state/count snapshot
- timestamp

The existing `ticket_admission_logs` can either be safely evolved into this generalized ledger or retained during a compatibility period. Do not maintain two independent authoritative admission histories.

## 5. Current repository integration audit

### 5.1 Strong foundations to reuse

**Issued tickets:** `App\Models\Event\IssuedTicket` already represents one real attendee ticket with a UUID, hashed secure token, status, check-in metadata, `presence_state`, `entry_count`, `exit_count` and `last_admission_at`.

**Secure ticket issuance:** `TicketIssuanceService` creates `btk_<uuid>.<HMAC>` bearer tokens and stores only SHA-256 token hashes. This is the correct entitlement anchor. Credentials should link to `issued_tickets.id`; do not create another booking-level pseudo-ticket identity.

**Atomic admission:** `TicketAdmissionService` already uses a DB transaction plus `lockForUpdate()` on the issued ticket and validates payment/status/organizer/staff ownership. This should become/refactor into the single generalized Access service.

**POS:** `BoxOfficeSaleService` already performs idempotent sales, authoritative pricing, locked inventory, fee resolution, booking creation, ticket issuance and ledger recording. Credential issue should happen after ticket issuance through a separate credential service, preserving sale idempotency.

**Team/RBAC:** `OrganizerStaff`, assignments and `config/staff.php` already support event-scoped staff and scanner permission checks. Extend this RBAC rather than create credential-specific users.

**Payments V2:** `EventAdditionalFee`, `AdditionalFeeCalculator`, immutable fee lines and sales-channel-aware pricing exist. Initial credential fees can use an event additional-fee code. Replacement transactions need their own explicit charge/ledger transaction path because they occur after booking; do not mutate historical ticket price/payment orders.

### 5.2 Current risks/gaps that must be fixed as part of integration

1. **Re-entry is coupled to Box Office.** `TicketAdmissionService` currently treats `event.box_office_enabled` as the special/re-entry switch. Online-ticket venue events can require wristbands/re-entry without being POS-only. Access policy must replace this coupling.

2. **Legacy `scanned_tickets` JSON still exists.** Organizer/Admin scanner controllers calculate dashboards and expose manual `ticketScanStatusChanged()` mutations against booking JSON. This can diverge from `issued_tickets`. New work must make `issued_tickets` + admission ledger authoritative and deprecate/remove arbitrary scan-status rewrites.

3. **Scanner app does not yet expose direction.** Backend organizer/staff admission supports `direction`, but current Flutter `api_client.dart` sends only `booking_id` to organizer/admin QR endpoints and still exposes manual scanned-status update APIs. Entry/Exit/Gate mode needs an additive API/app contract.

4. **Admin scanner parity gap.** Current Admin scanner `check_qrcode` does not accept/pass `direction` while Organizer does. Unify through a versioned/shared admission request.

5. **Staff scanner is separate from current Flutter role model.** Backend has `/api/staff-scanner` with event assignments, but current Flutter `UserRole` is admin/organizer. Credential desk/gate operations need staff login/role support without giving organizer credentials to temporary venue workers.

6. **Event form coupling.** Re-entry controls currently appear inside the Box Office event block. Move Access & Credentials into an independent event section/settings page so venue events and online-sale events can enable physical credentials safely.

7. **Email wording is currently one-admission oriented.** `TicketDeliveryService` appends “Each QR is valid for one admission only.” Credential-required/re-entry events need policy-aware messaging and collection instructions.

8. **No physical credential inventory/assignment lifecycle exists.** Must be additive tables; never repurpose ticket QR files, `booking_id`, `legacy_unique_id`, or `scanned_tickets`.

9. **Replacement cannot be deletion.** Revocation + replacement history is mandatory to prevent a found/lost old band from becoming valid again.

10. **Offline multi-gate re-entry is unsafe without reconciliation.** V1 should remain online-authoritative. Offline support is a later explicit protocol, not a silent fallback.

## 6. Service boundaries

Recommended backend services:

- `CredentialInventoryService` — create/import batches, validate unique identifiers, inventory lifecycle.
- `CredentialAssignmentService` — ticket + credential binding, activation and idempotency.
- `CredentialReplacementService` — charge validation, revoke old, activate new atomically.
- `AccessPolicyService` — resolve event/date/ticket/zone/re-entry policy.
- `AccessControlService` — generalized successor/facade around `TicketAdmissionService`; accepts either ticket token or credential token and resolves to one `IssuedTicket`.
- `CredentialChargeService` — post-sale replacement/credential charge integration with Payments V2/ledger.

Controllers must stay thin. All ownership, state transitions and financial authority stay server-side.

## 7. Admission algorithm

For every scan:
1. Normalize scanned token/UID.
2. Resolve as active credential first or secure BookTKIT ticket token as allowed by event policy.
3. Resolve exactly one `IssuedTicket`.
4. Start DB transaction and lock ticket + active credential assignment/state needed for the transition.
5. Verify event, payment/free state, ticket status, organizer ownership, staff assignment/permission, credential lifecycle, gate/zone and validity.
6. Evaluate requested action and current presence state.
7. Evaluate re-entry limit.
8. Record append-only scan result (including denials where useful).
9. Update authoritative presence/count state only for allowed transition.
10. Commit and return a typed result.

Use database unique constraints plus transactions; do not rely on UI disabling to prevent double assignment or concurrent entry.

## 8. RBAC additions

Extend existing staff roles/permissions with granular permissions such as:
- `credentials.issue`
- `credentials.replace`
- `credentials.inventory`
- `credentials.revoke`
- `access.scan_entry`
- `access.scan_exit`
- `access.override`
- `access.reports`

A normal gate scanner cannot replace credentials or override limits. Overrides require supervisor permission and an immutable reason/audit record.

## 9. UX surfaces

### Organizer — Access Control
- Overview / live attendance
- Access settings
- Credential batches & inventory
- Issue / collection desk
- Replacements
- Gates & zones
- Scan history
- Team permissions
- Reports

### POS
After completed sale: **Issue Credential** action per issued ticket. Failure to issue must leave the completed sale intact and place ticket in “credential pending collection.”

### Collection desk
Two-scan flow:
1. Scan customer ticket
2. Scan physical credential
3. Confirm assignment

Target: no manual token typing in normal operation.

### Scanner
Explicit operating mode:
- ENTRY
- EXIT
- CHECKPOINT (later/zone-aware)

Device should be assigned/limited to an event/gate where practical.

### Customer
Ticket/booking page and PDF/email show:
- credential collection required/not required
- collection point/instructions
- issued status (without exposing reusable credential secret)
- replacement rules/fee where appropriate

## 10. Financial integration

Use existing Payments V2 concepts, but distinguish two timings:

**At purchase:** mandatory/optional initial wristband/card charge can be an `EventAdditionalFee` included in authoritative checkout/POS pricing.

**After purchase:** lost/damaged replacement is a new post-sale transaction. Create an auditable charge/payment record linked to issued ticket + replacement. Do not edit the original booking/payment order totals.

Support cash/card/UPI/online according to channel. Replacement completion must be idempotent; payment success must not create two replacement credentials.

## 11. Safe phased implementation

### Phase 0 — contract and regression baseline
- Freeze current ticket/POS/scanner behavior with tests.
- Add fixtures for paid/free/box-office issued tickets.
- Add concurrent admission tests around current `TicketAdmissionService`.
- Document scanner JSON contracts.
- No production behavior change.

### Phase 1 — credential core (dark)
- Add access policy, credential batch, credential, assignment and audit schema/models/services.
- Add strong unique/index/foreign-key constraints.
- Add organizer/admin read-only inventory screens/APIs behind feature flag.
- No gate behavior changed yet.

### Phase 2 — issue/collection
- Add credential issuer staff permissions.
- Add ticket-scan + credential-scan assignment API/UI.
- Integrate pending/issued state into organizer and POS post-sale flow.
- Update customer ticket messaging.
- Keep existing ticket QR admission compatible while feature flag/policy controls credential enforcement.

### Phase 3 — unified Access engine
- Refactor `TicketAdmissionService` into/behind `AccessControlService`.
- Resolve both ticket and credential tokens to `IssuedTicket`.
- Move re-entry decision from `box_office_enabled` to event access policy.
- Add ENTRY/EXIT to admin/organizer/staff scanner contracts.
- Make issued-ticket/ledger dashboards authoritative.
- Deprecate manual `scanned_tickets` mutation.

### Phase 4 — replacement + payments
- Add replacement workflow, revocation, reason/audit.
- Add replacement charge/payment/ledger path.
- Verify old credential always fails immediately after replacement.
- Add refund/void policy explicitly if business requires it.

### Phase 5 — gates/zones/live operations
- Gate and zone management.
- Zone permissions and checkpoint scanning.
- Live inside/outside/re-entry/rejection dashboard.
- Supervisor override with audit.

### Phase 6 — RFID/NFC + hardware
- Add RFID/NFC reader adapter/device registration.
- Keep same credential/access services.
- Do not let hardware UIDs bypass server policy.

### Phase 7 — offline protocol (optional)
Only after threat/concurrency design. Define signed manifests, device identity, bounded validity, queued signed scan events, conflict resolution and reconciliation. Multi-gate re-entry should remain online-only until this is proven.

## 12. Migration/compatibility strategy

- Additive migrations first; no destructive changes in initial phases.
- Existing `issued_tickets` remain canonical entitlement records.
- Existing `presence_state/entry_count/exit_count` can remain the fast current-state projection while the access scan ledger is the audit source.
- Backfill access policy from existing `reentry_policy/max_reentries` only for events that currently use those settings; do not infer physical credential requirement.
- Keep old scanner endpoints compatible while adding versioned fields/endpoints. Remove legacy mutation only after web/Flutter clients are migrated.
- Never reissue existing secure ticket tokens as credential tokens.
- Credential feature must default OFF for existing events.

## 13. Security and privacy requirements

- No PII in QR/RFID payload.
- Hash bearer credential tokens where possible.
- Rate-limit scan/assignment endpoints appropriately.
- Enforce organizer/event/staff ownership on every mutation.
- Use least-privilege staff permissions.
- Immutable audit for issue/revoke/replace/override.
- Log device/operator, not secrets.
- Prevent credential enumeration.
- Unique active assignment constraints.
- Atomic assignment/replacement/admission.
- Do not store government ID documents for credential collection unless separately required; prefer ticket/OTP/manual “ID checked” flag.

## 14. Test gates before production

Minimum automated/staging coverage:
- online purchase -> issued ticket -> credential assignment -> entry -> exit -> allowed re-entry
- no-reentry denial
- limited re-entry boundary
- duplicate/concurrent ENTRY on two devices
- credential assigned twice
- two credentials assigned active to same ticket
- wrong event/organizer/staff assignment
- revoked/lost credential
- replacement makes old credential fail and new credential work
- free ticket
- POS sale -> issue credential
- failed credential assignment does not roll back completed POS sale
- payment pending/rejected ticket cannot receive usable admission
- voided/refunded/cancelled ticket behavior
- zone allow/deny when zones ship
- legacy ticket-only event remains unchanged
- Flutter scanner admin/organizer/staff contracts
- responsive organizer/collection/POS UI

Run state-changing tests on staging, not production.

## 15. Decisions locked by this architecture

1. Ticket and credential are separate concepts.
2. `IssuedTicket` remains the entitlement/admission anchor.
3. One generalized server-authoritative access engine handles ticket QR, QR wristband and future RFID/NFC.
4. Physical credentials are never hard-deleted when lost/replaced.
5. Re-entry belongs to Access Policy, not Box Office.
6. Admission transitions are transactional and concurrency-safe.
7. Credential charges are explicit financial records; historical ticket payments are not rewritten.
8. Existing events remain ticket-only unless explicitly enabled.
9. V1 is online-authoritative; offline multi-gate re-entry is deferred.
10. Implementation proceeds by the phases above, with regression gates between phases.

## 16. Audit conclusion

The current BookTKIT codebase is **ready for an additive credential/access subsystem without replacing the ticket/payment/POS foundations**. The safest path is to build on `IssuedTicket`, `TicketIssuanceService`, the transaction/locking pattern in `TicketAdmissionService`, existing staff assignments/RBAC, `BoxOfficeSaleService`, and Payments V2 fee/ledger primitives.

The main prerequisite during implementation is to eliminate split admission authority: `issued_tickets` plus the access ledger must become authoritative, while legacy booking-level `scanned_tickets` JSON/manual scan-status mutation is migrated out. No production credential feature should be enabled until Phase 0 regression tests and Phase 1 database constraints are verified on staging.
