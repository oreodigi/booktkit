# Box Office / Special Event Design

Status: Phase 1 design only. No feature code is authorized by this document.
Target branch: `staging`. Promotion to `main` requires staging tests and Founder PR approval.

## 1. Current-system findings

- Event type is currently constrained by `EventFormRequest` to `venue|online`. Special Event therefore remains a venue event; it is represented by `events.box_office_enabled=true`, not a third `event_type`.
- Existing admin RBAC (`RolePermission` + `HasPermission`) is coupled to the `admin` guard and JSON menu permissions. Staff authorization must be separate and assignment-scoped.
- Current guards are customer/admin/organizer plus Sanctum variants. Staff needs its own Eloquent provider, session guard and Sanctum guard.
- `AuthoritativeTicketPricingService` owns ticket/variation/early-bird pricing but availability checking is not itself a concurrency reservation. Box-office sale finalization must re-lock inventory in the same DB transaction that creates the booking and decrements availability.
- `TicketIssuanceService` is idempotent per booking and produces the canonical secure `btk_` QR token. Box-office sales reuse it unchanged.
- `TicketAdmissionService` already locks an issued-ticket row atomically and rejects inactive/already-used tickets. Phase 7 extends this state machine rather than creating a second scanner path.
- `TicketDeliveryService` currently delivers by email only and requires an email address. Box-office email is optional, so delivery must be channel-aware and non-blocking.
- `PaymentLedgerService` currently records ledger entries from a `PaymentOrder`; counter receivables/reversals need additive ledger semantics that do not pretend cash/UPI/card collected by the organizer passed through Razorpay.
- `PlatformFeeCalculator` currently reads organizer online fee settings. It will gain a separate box-office fee configuration with fallback to the existing online fee.
- `Booking` already uses legacy camelCase fields `paymentMethod`, `gatewayType`, `paymentStatus`; additive box-office columns must preserve these for existing flows.
- Existing scanner `OrganizerScannerController::events` has an ownership leak when a caller supplies an arbitrary event ID: displayed events are organizer-filtered, but bookings are loaded from the caller-supplied ID set without reapplying organizer ownership. Phase 7 fixes this.
- The standalone Flutter scanner currently supports only admin/organizer identities. The organizer app has no staff scanner contract today. Phase 7 adds staff mode to the organizer app as requested, while preserving existing scanner clients.

## 2. Schema

All migrations are additive. Existing rows receive backward-compatible defaults.

### events
- `box_office_enabled boolean not null default false`
- `reentry_policy string/enum: none|unlimited|limited, default none`
- `max_reentries unsigned integer nullable`
- Constraint in application validation: `max_reentries` required and >= 1 only when policy=limited; box office may only be enabled for `event_type=venue`.

### box_office_locations
- `id`
- `event_id` FK events, cascade delete
- `name`
- `address nullable`
- `active boolean default true`
- timestamps
- unique `(event_id,name)`; index `(event_id,active)`

### organizer_staff
- `id`
- `organizer_id` FK organizers
- `name`, `phone nullable`, `email nullable`, `username`
- `password` hashed
- `status active|disabled`
- `must_change_password boolean default true`
- `last_login_at nullable`
- timestamps
- unique `(organizer_id,email)` when email is present; unique `(organizer_id,username)`. Login resolves organizer scope + username; implementation must avoid ambiguous global usernames.

### staff_event_assignments
- `id`, `staff_id`, `event_id`
- `role sales_agent|ticket_checker|box_office_supervisor`
- `location_id nullable`, `gate_name nullable`
- timestamps
- FKs to staff/event/location
- unique assignment tuple preventing duplicates
- invariant: event belongs to staff.organizer; location belongs to event; sales/supervisor assignments require a location; checker may omit location.

### bookings additions
- `source online|box_office` default `online`
- `sold_by_type organizer|staff nullable`
- `sold_by_id nullable`
- `box_office_location_id nullable`
- `shift_id nullable`
- `sale_uuid uuid nullable unique`
- `payment_method cash|upi|card|bank_transfer|complimentary nullable`
- `payment_reference nullable`
- `complimentary_reason nullable`
Existing `paymentMethod/gatewayType/paymentStatus` remain populated compatibly; box-office bookings use completed status and a non-gateway/counter gateway type.

### box_office_shifts
- fields requested: staff/event/location, opened_at/opening_float, closed_at/declared_cash, expected_cash, variance, status, verified_by, notes
- monetary values stored in integer minor units (paise), not floats
- add `verified_by_type organizer|staff nullable`, `verified_at nullable`
- partial/business invariant: one open shift per staff/event/location.

### box_office_voids
Required to implement request/approval safely without deleting sales:
- booking_id, requested_by_type/id, reason, status pending|approved|rejected
- approved_by_type/id, decision_reason nullable, requested_at, decided_at
- unique pending void per booking enforced transactionally.

### box_office_reprints
- booking_id, issued_ticket_id nullable, actor_type/id, reason nullable, printed_at, IP/device metadata.

### issued_tickets additions
- `presence_state outside|inside default outside`
- `entry_count unsigned integer default 0`
- `last_scan_at nullable`
Existing `checked_in_at/checked_in_by_*` remain for backward compatibility and single-entry events.

### ticket_admission_logs additions
- `direction entry|exit nullable`
- `gate nullable`
For old/non-special scans, direction may remain null or be recorded as entry while preserving current semantics.

### audit log
New `organizer_activity_logs` (or a generic existing audit table if inspection during implementation finds one suitable): organizer_id, actor_type/id, action, subject_type/id, event_id nullable, location_id nullable, metadata JSON, ip_address, device_name/user_agent, created_at. Never store passwords/tokens.

### payment ledger / settings
Add box-office fee configuration to organizer/admin payment settings (type/value/fixed/bearer as applicable), falling back to online settings when unset. Extend ledger entries so counter fees are organizer receivables and void approvals create immutable reversal entries referencing the original sale/booking. Do not mutate historical ledger rows.

## 3. Guards and authorization

### Guards
- `staff`: session driver, provider `organizer_staff`; web POS/team-adjacent staff panel.
- `staff_sanctum`: Sanctum driver, same provider; organizer-app staff scanner/POS APIs.
- Staff model uses `Authenticatable` + `HasApiTokens`.
- `/staff/login` is rate-limited by normalized organizer/staff identity + IP. Successful first login with `must_change_password=true` may access only password-change/logout endpoints.
- Disabling staff revokes all Sanctum tokens and invalidates web sessions using an auth-version/session invalidation mechanism; middleware also checks active status on every request so revocation is immediate even before session garbage collection.

### Authorization layers
1. Authentication guard.
2. `EnsureStaffActive`.
3. `EnsureStaffAssignment`: resolves assignment server-side and verifies staff -> organizer -> event -> location/gate relationship.
4. Permission check from a fixed config map keyed by assignment role.
5. Domain service rechecks ownership inside state-changing transactions.

Never authorize from request-supplied organizer_id, event_id, location_id, staff_id or booking_id alone.

Organizer actions use existing organizer guard plus event ownership checks. Organizer is treated as having all box-office operational permissions for their own events, including complimentary issuance and void approval.

## 4. Permission matrix

| Permission | sales_agent | ticket_checker | box_office_supervisor | organizer |
|---|---:|---:|---:|---:|
| sell_tickets | yes | no | yes | yes |
| print_tickets | yes | no | yes | yes |
| reprint_tickets | yes | no | yes | yes |
| request_void | yes | no | yes | yes |
| approve_void | no | no | own location | yes |
| view_own_sales | yes | no | yes | yes |
| view_location_sales | no | no | own location | yes |
| scan_entry | no | yes | no | yes |
| scan_exit | no | yes | no | yes |
| issue_complimentary | no | no | no by default | yes |
| open_close_own_shift | yes | no | yes | organizer POS does not require staff shift |
| verify_shift | no | no | no by default | yes |

Role permissions live in `config/box_office.php`; DB assignments store role names, not arbitrary permission JSON. This keeps roles fixed now while allowing a later role/permission persistence layer without changing enforcement call sites.

## 5. API surface

All JSON mutations require idempotency where applicable, server-derived ownership, validation and audit logging.

### Staff auth
- `POST /api/staff/login`
- `POST /api/staff/change-password`
- `POST /api/staff/logout`
- `GET /api/staff/me`

### Organizer team
- `GET/POST /api/organizer/team`
- `GET/PUT /api/organizer/team/{staff}`
- `POST /api/organizer/team/{staff}/disable`
- `POST /api/organizer/team/{staff}/reset-password`
- `GET/POST/PUT/DELETE /api/organizer/team/{staff}/assignments[/{assignment}]`
- `GET /api/organizer/team/{staff}/activity`

### Box office
- `GET /api/box-office/events` assigned/owned only
- `GET /api/box-office/events/{event}/inventory?date=...&slot=...`
- `POST /api/box-office/sales` with sale_uuid, ticket IDs/variations/qty and customer/payment metadata; never client prices
- `GET /api/box-office/bookings/{booking}/print?format=80mm|a4`
- `POST /api/box-office/bookings/{booking}/reprint`
- `POST /api/box-office/bookings/{booking}/deliver`
- `POST /api/box-office/bookings/{booking}/void-request`
- `POST /api/box-office/voids/{void}/approve|reject`

### Shifts
- `POST /api/box-office/shifts/open`
- `GET /api/box-office/shifts/current`
- `POST /api/box-office/shifts/{shift}/close`
- `POST /api/organizer/box-office/shifts/{shift}/verify`

### Reports
- `GET /api/organizer/box-office/reports`
- `GET /api/organizer/box-office/reports.csv`
- `GET /api/organizer/box-office/live`

### Scanner
- `GET /api/staff/scanner/events`
- `POST /api/staff/scanner/scan` body: QR token, direction, assignment/gate context
Existing organizer/admin scanner routes remain compatible. Organizer scanner event/bookings ownership leak is fixed in Phase 7.

## 6. Screens

### Event wizard
Event chooser cards: Online Event, Venue Event, Special Event. Special Event opens the same canonical venue wizard and sets `box_office_enabled=true`; it does not create a new event_type. Additional Box Office section manages counters and re-entry policy/max re-entries. Edit/duplicate use the same canonical form state.

### Organizer web
- Team: staff list, status, last login, create/edit/reset/disable, assignments, activity.
- Box Office POS: organizer-owned event/date/slot, inventory, customer, payment, sale confirmation/print.
- Locations and shifts.
- Void approval queue.
- Reports/dashboard with requested filters, online vs counter comparison, cash reconciliation, voids/reprints and live admission metrics.

### Staff web POS
Mobile-first: assigned event -> date/slot -> tickets -> customer -> payment -> confirm -> print/deliver. Shift status is persistent and obvious. No organizer-wide navigation.

### Organizer Flutter app staff mode
Login detects organizer vs staff without weakening either guard. Staff sees only assigned scanner/POS capabilities. Checker flow: assigned event/gate -> Entry/Exit toggle -> camera -> large success/rejection state + sound/haptic feedback -> recent local results. Server response remains authoritative.

## 7. Transaction boundaries

### Sale
Within one DB transaction: resolve assignment/open shift -> lock relevant ticket/variation inventory rows -> recompute authoritative price -> validate remaining stock -> create booking using unique sale_uuid -> decrement shared inventory -> calculate counter platform fee -> append ledger receivable -> issue canonical tickets. A duplicate sale_uuid returns the original completed result. External delivery/printing happens after commit.

Online checkout must use the same inventory-locking primitive so online and counter channels cannot race independently. Phase 4 therefore extracts/reuses a shared inventory reservation/decrement service rather than adding a box-office-only lock.

### Void
Lock booking + issued tickets + relevant inventory. Reject if any ticket has admission history indicating use. Mark tickets void, mark booking/counter sale voided without deleting it, restore exactly the inventory consumed by the sale, append ledger reversal, finalize void request, audit. Entire operation is idempotent.

### Admission
Lock issued ticket. Non-special event follows existing one-time admission behavior. Special event applies entry/exit state machine and event re-entry limits atomically, updates presence state/count/last scan, and appends admission log including direction/gate.

## 8. Migration / rollout plan

1. Add nullable/defaulted event, booking, issued-ticket and admission-log columns plus new tables/indexes. No destructive rename/drop.
2. Backfill only deterministic defaults: existing events box_office_enabled=false; existing bookings source=online; issued tickets presence_state derives from checked_in_at (inside for already checked-in tickets only if needed for special-mode logic; otherwise special mode is false so legacy behavior remains authoritative).
3. Add models/config/guards/middleware behind box-office-enabled routes.
4. Add Special Event wizard fields while preserving venue/online validation.
5. Add staff/team management.
6. Add shared race-safe inventory service and box-office sale/print/void flow.
7. Add shifts and reconciliation.
8. Add reports/finance receivable presentation.
9. Extend admission state machine and Flutter staff mode; repair scanner ownership leak.
10. Add audit coverage and full regression suite.
11. Each phase deploys only to staging, runs migrations/tests there, and stops for approval. Main remains untouched until Founder-approved staging -> main PR.

## 9. Risks and controls

- **Inventory race / oversell (critical):** current pricing availability check is not sufficient. Use a shared locked inventory mutation for online + box office and concurrency tests.
- **Cross-organizer/staff data exposure (critical):** enforce assignment/ownership at middleware, query and transactional service layers. Add negative tests for forged IDs. Fix current scanner booking leak.
- **Counter money is not gateway money (critical accounting):** ledger counter fees as organizer receivables, never fake Razorpay customer payments. Reversals are immutable compensating entries.
- **Void/restock corruption:** retain sale line snapshot sufficient to restore the exact ticket/variation quantities; idempotent approval and row locks.
- **Legacy booking schema:** keep `paymentMethod/gatewayType/paymentStatus` behavior; additive normalized fields are box-office-specific until a separately approved migration.
- **Immediate staff revocation:** active-status middleware on every request plus token revocation/session version invalidation.
- **Re-entry compatibility:** only `box_office_enabled` special events use entry/exit state. Existing events retain current single check-in semantics.
- **QR security:** reuse existing signed/hash-backed issued-ticket tokens; reprint never regenerates identity.
- **Optional delivery:** email/SMS/WhatsApp failures cannot roll back a completed counter sale. Existing delivery currently supports email only; other channels require existing integrations to be verified before implementation.
- **Flutter compatibility:** add staff identity as an additive auth mode; do not reinterpret organizer/admin tokens as staff tokens.
- **Large rollout:** feature-gate box-office UI/API by event flag and preserve rollback ability. Additive migrations remain safe if UI/routes are disabled.

## 10. Required verification by phase

- PHPUnit: authorization isolation, role permissions, disabled staff, pricing/inventory locking, sale idempotency, void/restock/reversal, shift math, fee receivable, admission state machine/re-entry.
- Concurrency regression: last-ticket simultaneous online + counter attempts result in exactly one successful inventory consumption.
- Existing Laravel payment/ticket/admission/event tests must remain green.
- Flutter: format/analyze/tests/debug APK for changed organizer app.
- Playwright staging acceptance: Special Event + 2 counters -> staff creation/assignment -> cash/UPI sales + print -> entry/exit/re-entry -> reports -> shift verification.
- Existing smoke/auth/organizer/event-creation staging gate must pass with zero failed/skipped/flaky tests before any promotion PR.

## Phase 1 stop condition

Phase 1 changes documentation only: `docs/BOX-OFFICE-DESIGN.md`. No migrations, models, routes, controllers, UI, payment, ticket, scanner or Flutter feature code is to be written until Founder approval.
