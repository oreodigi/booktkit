# BookTKIT Access & Credential System

Status: implemented foundation and operational phases on canonical main as of 6 October 2026. This document replaces the earlier architecture-only status.

## Model
A ticket/pass is the commercial entitlement. A credential is a physical/digital carrier used to prove that entitlement. Credentials never replace `issued_tickets` as the entitlement authority.

Current credential vocabulary includes QR wristband, RFID wristband, RFID card, NFC card, QR badge and physical ID. QR/RFID identifiers must remain opaque and must not expose customer PII.

## Implemented domains on main
- Event access policy and credential configuration.
- Credential batch/inventory lifecycle.
- Ticket-to-credential assignment and organizer collection/issuance console.
- POS access to credential assignment after successful sale.
- Replacement/revocation workflow with configured replacement fee and payment/reference capture.
- Gates and zones.
- Append-only access scan/audit data and live operations metrics.
- Unified credential/ticket admission service.
- Organizer and assignment-scoped staff scanner APIs/sessions.
- Explicit entry/exit scanner mode and persistent selected gate.
- Re-entry limits and date-scoped presence state.
- Multi-day pass/date entitlement enforcement.
- Audited supervisor override.
- RBAC permissions for credential/access operations.

## Online collection
1. Server finalizes paid/free booking and issues `IssuedTicket`.
2. Customer presents secure BookTKIT ticket QR at collection.
3. Authorized issuer scans ticket QR and an unassigned physical credential.
4. Server validates organizer/event/ticket/credential state and binds atomically.
5. Active credential can then resolve to the same issued ticket at admission.

## POS
POS sale remains financially independent of credential hardware. Complete the authoritative sale first, then issue/assign a credential. Credential/printer failure must not roll back a valid completed sale.

## Admission state
The server owns:
`outside -> ENTRY -> inside -> EXIT -> outside -> RE-ENTRY -> inside`.

Initial entry is not a re-entry. Event policy controls re-entry limits. Entry while already inside and exit while already outside are denied unless an authorized audited override applies.

Admission requires event/organizer/staff assignment, gate context where configured, ticket/credential validity, pass/date entitlement and current presence state. State changes occur transactionally.

## Replacement
Never delete the old credential. Replacement revokes the old credential, records reason/operator/fee reference, activates the new credential and preserves audit history. A revoked/lost/replaced old credential must permanently fail admission.

## Authority
`issued_tickets` + active credential assignment + access policy + unified admission engine are canonical. Do not create a second admission authority from `bookings.scanned_tickets`, manual client state or QR-decoded claims.

## Remaining/future work
RFID/NFC hardware adapters and any offline multi-gate protocol remain hardware/reconciliation work unless current code explicitly proves otherwise. Offline admission must not be silently enabled; it requires device identity, signed bounded manifests/events and conflict reconciliation.

## Testing priorities
Cover duplicate/concurrent assignment, revoked replacement token, wrong organizer/event/gate, staff permission/assignment, repeated entry/exit, re-entry exhaustion, pass date mismatch, supervisor override audit and concurrent scans.
