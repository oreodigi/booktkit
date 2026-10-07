# BookTKIT — Functional Specification Document (FSD)

**Platform:** BookTKIT event ticketing, Box Office/POS, Payments, Organizer Workforce and Venue Access
**Channels:** Public Website · Customer Web · Organizer Web · Staff Web · Admin · POS · Scanner (Web PWA) · Customer App · Organizer App · Scanner App · API
**Repository baseline:** `oreodigi/booktkit` main, snapshot reconciled 6 October 2026 (uploaded archive `booktkit-main`)
**Document status:** DRAFT — Iteration 4 (Sections 0–6; Event, Ticket/Pass, Booking/Checkout, Issuance/Delivery, POS and Team use cases; Legacy conflict register)

---

## 0. Document Control

| Item | Value |
|---|---|
| Document | `docs/BOOKTKIT-FUNCTIONAL-SPECIFICATION.md` |
| Owner | BookTKIT Product Owner |
| Author | Principal Product & System Architect (Claude) |
| Audience | Product, engineering, QA, operations, new technical teams |
| Authority | Canonical requirements in this document override legacy implementation. Current code is evidence of implementation only. |
| Related documents | `AGENTS.md`, `project instructions.md`, `docs/CANONICAL-ARCHITECTURE.md`, `docs/OPERATIONS-SOURCE-OF-TRUTH.md`, `docs/PAYMENTS-V2.md`, `docs/BOX-OFFICE-DESIGN.md`, `docs/BOOKTKIT-ACCESS-CREDENTIAL-SYSTEM.md`, `docs/mobile/*` |
| Evidence limits | Static code reading only. No runtime, staging or production behavior was executed. Items marked SUSPECTED require staging verification. |

### 0.1 Revision History

| Version | Date | Author | Details |
|---|---|---|---|
| 0.1 | 06-Oct-2026 | Architect | Repository inventory, channel/actor model, roles matrix (current + canonical), navigation map, screen registry (IDs reserved), use case catalogue (IDs reserved), Event domain use cases UC-016 – UC-030 fully specified, Event state machine, Legacy/Conflict register LC-001 – LC-046, open decisions. |
| 0.2 | 06-Oct-2026 | Architect | Ticket, variation, seat map, pass, coupon and inventory domain: §6.4 model, admission vocabulary and precedence, UC-031 – UC-042 specified; LC-047 – LC-057; DEC-23 – DEC-30; AC-TKT-01 – 12; traceability §17.2. |
| 0.3 | 06-Oct-2026 | Architect | Booking & checkout (UC-043 – UC-055) and ticket issuance & delivery (UC-096 – UC-100) specified; channel map; booking and issued-ticket state machines; LC-046 upgraded to verified; LC-058 – LC-072; DEC-31 – DEC-39; AC-BKG/AC-ISS; traceability §17.3. |
| 0.4 | 06-Oct-2026 | Architect | POS / Box Office (UC-071 – UC-088) and Organizer team/RBAC (UC-089 – UC-095) specified; POS workspace and Team screen specs; POS sale, hold, shift and void state machines; LC-073 – LC-082; DEC-40 – DEC-44; AC-POS/AC-TEAM; traceability §17.4. |

### 0.2 Use Case Sign-off Status

Sign-off columns are completed by the Product Owner. "Specified" means the use case has a full table in this document; "Reserved" means the ID is allocated and will be specified in a later iteration.

| ID | Use Case | Spec status | Sign-off | Date | Authority | Remarks |
|---|---|---|---|---|---|---|
| UC-001 – UC-010 | Authentication & accounts | Reserved | — | — | — | Iteration 3 |
| UC-011 – UC-015 | Organizer onboarding, profile, Payouts/KYC | Reserved | — | — | — | Iteration 6 |
| UC-016 – UC-030 | Event management | **Specified** | Pending | — | — | This iteration |
| UC-031 – UC-042 | Tickets, variations, seat maps, passes | **Specified** | Pending | — | — | Iteration 2 |
| UC-043 – UC-055 | Booking & checkout | **Specified** | Pending | — | — | Iteration 3 |
| UC-056 – UC-070 | Payments, fees, settlement, refunds | Reserved | — | — | — | Iteration 5 |
| UC-071 – UC-088 | POS / Box Office | **Specified** | Pending | — | — | Iteration 4 |
| UC-089 – UC-095 | Organizer team / RBAC | **Specified** | Pending | — | — | Iteration 4 |
| UC-096 – UC-100 | Ticket issuance & delivery | **Specified** | Pending | — | — | Iteration 3 |
| UC-101 – UC-110 | Access credentials | Reserved | — | — | — | Iteration 7 |
| UC-111 – UC-125 | Admission & scanner | Reserved | — | — | — | Iteration 7 |
| UC-126 – UC-150 | Reporting, AI, notifications, admin, content, mobile | Reserved | — | — | — | Iterations 8–9 |

---

## 1. Purpose, Scope and Conventions

### 1.1 Purpose
This document specifies how BookTKIT is intended to behave from a product, user-flow, business-rule and system-behavior perspective. A product manager, developer or tester should be able to determine correct behavior for any screen or action without reverse-engineering the repository.

### 1.2 Scope
In scope: Laravel website/back end (`source/website`), customer app, organizer app, scanner app, and all actors, channels and domains listed in §2–§4.
Out of scope for canonical behavior (pending decision, see §15): the supplier-era Shop module, supplier-era multi-gateway checkout, supplier-era organizer wallet/withdrawals, blog/CMS beyond basic content management.

### 1.3 Statement classification
Every normative statement carries one tag:

| Tag | Meaning |
|---|---|
| **[VERIFIED]** | Current behavior confirmed by reading the code in the baseline snapshot. |
| **[CANONICAL]** | Required BookTKIT behavior, derived from canonical docs or approved product rules. Implementation must conform. |
| **[LEGACY]** | Supplier-era behavior still reachable. Not authoritative. Scheduled for adapter, migration or removal. |
| **[PROPOSED]** | Architect's recommended behavior awaiting Product Owner approval. |
| **[UNKNOWN]** | Cannot be established from code or docs. Requires a product decision (see §15). |
| **[SUSPECTED]** | Probable defect from code reading that must be reproduced on staging before it is treated as fact. |

### 1.4 Severity scale
P0 security / money / data loss / admission authority · P1 core workflow broken · P2 major functional inconsistency · P3 UX / architecture inconsistency · P4 cleanup / technical debt.

### 1.5 Conflict classification
**Local defect** (architecture clear, code wrong) · **Architecture conflict** (two parts implement different rules) · **Legacy conflict** (supplier behavior competes with canonical model) · **Missing architecture** (no stable product rule yet).

### 1.6 Terminology & Acronyms

| Term | Definition |
|---|---|
| Event | A sellable occasion owned by one organizer. Types: `online`, `venue`, `box_office`. |
| Event date / session | A row in `event_dates` for multi-date events. Single-date events store dates on the event itself. |
| Ticket (ticket type) | A commercial product definition in `tickets` (price, inventory, variations, admission configuration). |
| Variation | A named price/inventory tier inside a ticket (`tickets.variations` JSON). |
| Pass product | A multi-date product in `event_pass_products` that grants date-scoped entitlements. |
| Booking | Commercial order record in `bookings` (supplier-era table, still the order of record). |
| Payment order | Payments V2 record in `payment_orders` that snapshots price, fees and settlement mode. |
| Issued ticket | One attendee entitlement in `issued_tickets`; the canonical admission authority. |
| Pass entitlement | Date-scoped right attached to an issued ticket (`pass_entitlements`). |
| Credential | Physical/digital carrier (QR wristband, RFID wristband/card, NFC card, QR badge, physical ID) in `credentials`. |
| Assignment | Binding of credential to issued ticket (`ticket_credentials`). |
| Admission | Server decision to admit (ENTRY) or record EXIT for an issued ticket. |
| Presence state | `outside` / `inside` per issued ticket (and per event date for passes). |
| Gate / Zone | Physical control points (`event_gates`, `event_access_zones`). Gate mode: `entry`, `exit`, `entry_exit`. |
| POS / Box Office | Counter sales channel and its operational workspace. |
| Box office location | A named counter for an event (`box_office_locations`). |
| Hold | Saved POS cart awaiting payment (`box_office_holds`). |
| Shift | Staff cash session at a counter (`box_office_shifts`). |
| Organizer staff | Operational identity belonging to an organizer (`organizer_staff`), not an organizer superuser. |
| Settlement mode | `booktkit_managed` or `razorpay_split` (Razorpay Route / Direct). |
| Fee rule | Payments V2 rule in `payment_fee_rules`. |
| KYC | Organizer payout verification (Razorpay linked account). |
| RBAC | Role-based access control. |
| PWA | Progressive Web App (legacy web scanner). |
| IDOR | Insecure direct object reference (acting on another owner's record by ID). |
| Minor units / paise | Integer currency amounts (₹1 = 100 paise) used by Payments V2. |

---

## 2. System Overview and Channel Architecture

### 2.1 Applications [VERIFIED]

| Application | Path | Technology | Role |
|---|---|---|---|
| Website & shared back end | `source/website` | Laravel 9 / PHP 8.3, Blade, Laravel Mix | Public site, customer web, organizer web, staff web, admin, all APIs, payments, POS, access |
| Customer app | `source/customer-app` | Flutter (274 Dart files) | Discovery, checkout, bookings, wishlist, support |
| Organizer app | `source/organizer-app` | Flutter (123 Dart files) | Dashboard, events (online/venue only), tickets, seat maps, bookings, withdrawals, support, legacy scanner |
| Scanner app | `source/scanner-app` | Flutter (23 Dart files) | Organizer/admin/staff login, events, gates, entry/exit scanning, history |
| DevTools | `tools/booktkit-devtools` | Node/Playwright | QA orchestration |
| Deploy | `deploy` | Shell | cPanel staging/production deployment and rollback |

### 2.2 Route surfaces [VERIFIED]

| File | Mounted at | Purpose | Size |
|---|---|---|---|
| `routes/web.php` | `/` | Public site, customer web, checkout, 16 legacy gateway callbacks (event & shop), CMS, admin login, `/migrate`, cron | 330 lines |
| `routes/organizer.php` | `/organizer`, `/staff`, `/ai` | Organizer panel, staff web, AI generation, AI-token gateway callbacks | 244 lines |
| `routes/admin.php` | `/admin` | Admin panel | 938 lines |
| `routes/api.php` | `/api` | Customer API, legacy organizer API, legacy scanner API, Payments V2 API, webhook | 285 lines |
| `routes/scanner_api.php` | `/api/scanner` | Current scanner API for organizer/admin/staff | 44 lines |

### 2.3 Authentication guards [VERIFIED]

| Guard | Actor | Used by |
|---|---|---|
| `customer` (session) | Customer | Customer web |
| `sanctum` | Customer | Customer app `/api/customers/*` |
| `organizer` (session) | Organizer (and staff — see LC-003) | Organizer web |
| `organizer_sanctum` | Organizer | Organizer app, scanner app, `/api/v1/organizer/payments` |
| `staff` (session) | Organizer staff | Staff web `/staff/*` |
| `staff_sanctum` | Organizer staff | Scanner app staff mode, `/api/staff-scanner/*` |
| `admin` (session) | Admin / sub-admin | Admin panel |
| `admin_sanctum` | Admin | Scanner app admin mode |

### 2.4 Domain authority map

| Domain | Canonical authority [CANONICAL] | Current reality [VERIFIED] |
|---|---|---|
| Event create/edit/duplicate | `App\Services\Events\EventFormService` | Used by organizer web and admin web. **Not** used by organizer mobile API (LC-025). |
| Pricing | `AuthoritativeTicketPricingService` | Used by web Razorpay and POS. **Not** used by legacy `/api/event-booking` (LC-005) or free web booking path (LC-046). |
| Payment orders, fees, settlement | `PaymentOrderService`, `PaymentFeeRuleResolver`, `PlatformFeeCalculator`, `AdditionalFeeCalculator`, `SettlementRoutingService` | Web Razorpay + `/api/v1/payments`. POS bypasses payment orders (LC-016). |
| Booking finalization | `BookingFinalizationService` | Web Razorpay + `/api/v1/payments/razorpay/verify` only. |
| Ledger | `PaymentLedgerService` | Parallel `BoxOfficeLedgerService` for POS (LC-016). Legacy `transactions`/`organizers.amount` still maintained (LC-038). |
| POS sale | `BoxOfficeSaleService` | Organizer and staff POS. |
| Ticket issuance | `TicketIssuanceService` | Lazily on view/print/delivery for any `completed`/`free` booking. |
| Ticket delivery | `TicketDeliveryService` | After finalization; POS optional email. |
| Credentials | `CredentialInventoryService`, `CredentialAssignmentService`, `CredentialReplacementService` | Organizer web console. |
| Admission | `AccessControlService` (via `TicketAdmissionService`) | Current scanner API. Legacy `scanned_tickets` endpoints still live (LC-001). |
| Staff authorization | `EnsureStaffAssignment`, `EnsureOrganizerStaffRbac`, `OrganizerStaff::hasPermission` | Partial; dual-guard login bypass (LC-003). |

### 2.5 Canonical end-to-end journey [CANONICAL]

```
Organizer Signup/Login (UC-001/UC-004)
 → Payouts/KYC (optional for free events; see DEC-03) (UC-012)
 → Event list (UC-016) → Choose type (UC-017) → Create event wizard (UC-018)
     Details → Schedule (UC-019) → Location/Online access (UC-020) → Box office & re-entry (UC-021) → Media (UC-022)
 → Ticket settings (UC-023) → Tickets/variations (UC-031+) → Passes (UC-038+)
 → Access policy / credentials (UC-101+) → Fees visible (UC-060)
 → Publish (UC-026)
 → Sell: Online checkout (UC-043+) | POS (UC-071+)
 → Payment order → Finalize booking → Issue tickets (UC-096) → Deliver (UC-098)
 → Credential collection/assignment if required (UC-103)
 → ENTRY scan (UC-111) → EXIT scan (UC-112) → RE-ENTRY (UC-113)
 → Event ends (derived) → Reports (UC-126+) → Ledger reconciliation → Settlement/transfer (UC-064+)
```

---

## 3. Actors, Roles and Permissions

### 3.1 Actor catalogue

| Actor | Authentication | Channels | Description | Status |
|---|---|---|---|---|
| Guest / Visitor | None | Public web, customer app | Browses events, may book if guest checkout is enabled (`basic_settings.event_guest_checkout_status`) | [VERIFIED] |
| Customer | Email/password, Google, Facebook; Sanctum for app | Customer web, customer app | Books tickets, views bookings and QR tickets | [VERIFIED] |
| Organizer | Email/password (`organizers`), email verification, account status | Organizer web, organizer app, scanner app | Owns events, tickets, sales, team, payouts | [VERIFIED] |
| Organizer staff | Username/password under an organizer (`organizer_staff`), forced password change | Staff web, scanner app, organizer web (see LC-003) | Operational role with permissions and assignments | [VERIFIED] |
| Box office staff / POS operator | Organizer staff with `box_office.sell` | Staff POS | Sells at assigned event + location, requires open shift | [VERIFIED] |
| Credential issuer | Organizer staff with `credentials.issue` / `credentials.replace` | Organizer access console | Binds and replaces credentials | [VERIFIED] permission exists; route mapping broken (LC-035) |
| Entry / exit scanner staff | Organizer staff with `access.scan_entry` / `access.scan_exit` / `tickets.scan` | Scanner app | Admits/exits attendees at assigned events | [VERIFIED] |
| Box office supervisor | Staff role `box_office_supervisor` | Staff/organizer web | Voids, shift verification, overrides | [VERIFIED] |
| Admin | Admin session with role (`admins.role_id` → `role_permissions`) | Admin panel, scanner app | Platform operations | [VERIFIED] |
| Super admin | Admin with all permissions | Admin panel | Full platform control | [VERIFIED] |
| Finance / settlement operations | Admin with `Transaction` permission | Admin payments | Fee rules, refunds, transfers, reconciliation | [VERIFIED] as admin permission; no dedicated finance actor [UNKNOWN DEC-12] |
| Razorpay (system) | Webhook signature | `/api/v1/webhooks/razorpay` | Payment and transfer events | [VERIFIED] |
| Scheduler (system) | Public GET routes today (LC-008) | `/send-ticket`, `/check-payment`, `/send-push-notification-phone` | Queue processing | [VERIFIED] [CANONICAL: must move to cron/CLI] |

### 3.2 Organizer staff permission vocabulary [VERIFIED `config/staff.php`]

`events.view`, `events.manage`, `tickets.manage`, `bookings.view`, `bookings.manage`, `box_office.sell`, `box_office.reprint`, `box_office.void_request`, `box_office.void_approve`, `shifts.verify`, `reports.view`, `tickets.scan`, `credentials.issue`, `credentials.replace`, `credentials.inventory`, `credentials.revoke`, `access.scan_entry`, `access.scan_exit`, `access.override`, `access.reports`, `support.manage`, `team.manage`, `payments.view`, `ai.use`.

Custom permissions on a staff record **replace** (not merge with) the role defaults [VERIFIED `OrganizerStaff::permissions()`].

### 3.3 Staff role defaults [VERIFIED]

| Role | Department | Default permissions |
|---|---|---|
| Sales Agent | sales | box_office.sell, box_office.reprint |
| Cashier | sales | box_office.sell, box_office.reprint |
| Ticket Checker | admissions | tickets.scan, access.scan_entry, access.scan_exit |
| Credential Issuer | admissions | credentials.issue, credentials.replace |
| Box Office Supervisor | sales | sell, reprint, void_request, void_approve, shifts.verify, reports.view, tickets.scan, credentials.*, access.scan_entry/exit, access.override, access.reports |
| Event Manager | operations | events.view/manage, tickets.manage, bookings.view/manage, reports.view, tickets.scan, credentials.*, access.*, support.manage, ai.use |
| Support Staff | support | bookings.view, support.manage |

### 3.4 Assignment scope

| Scope | Current [VERIFIED] | Canonical [CANONICAL/PROPOSED] |
|---|---|---|
| Organizer | `organizer_staff.organizer_id` | Same; every staff action server-checks organizer ownership. |
| Event | `organizer_staff_assignments.event_id`; enforced by staff scanner and staff POS; **not** enforced inside organizer web area (LC-003) | Enforced for every staff action that touches an event, on every channel. |
| Box office location | `organizer_staff_assignments.box_office_location_id`; enforced for staff POS | Same. |
| Gate / zone | **Not modelled** (LC-010) | [PROPOSED] Optional `gate_id` on assignment; scanner must reject gates outside assignment. |

### 3.5 Permissions matrix — canonical [CANONICAL/PROPOSED]

Legend: V view · C create · E edit · D delete/archive · O operate · X override/approve · — none. "own" = organizer-owned records; "asg" = assigned events/locations only; "perm" = requires the named staff permission.

| Module | Guest | Customer | Organizer | Staff (perm, asg) | Admin (permission) |
|---|---|---|---|---|---|
| Public events | V | V | V | V | V |
| Own bookings & tickets | — | V (own) | — | — | V (Event Bookings) |
| Events | — | — | V C E D (own) | V `events.view`; C E `events.manage` (asg for E) | V C E D (Event Management) |
| Tickets / variations / seat maps / passes | — | — | V C E D (own) | V C E D `tickets.manage` (asg) | V C E D (Event Management) |
| Event publish/unpublish | — | — | O (own) | O `events.manage` [PROPOSED separate `events.publish`, DEC-14] | O |
| Event feature flag | — | — | [UNKNOWN DEC-13] | — | O |
| Bookings | — | — | V (own); E status for offline approval (own) | V `bookings.view`; E `bookings.manage` (asg) | V E (Event Bookings) |
| Booking delete | — | — | — [CANONICAL: no hard delete of commercial records] | — | — (archive only) |
| POS sell | — | — | O (own) | O `box_office.sell` (asg event+location, open shift) | — [UNKNOWN DEC-15] |
| POS hold/resume | — | — | O (own) | O `box_office.sell` (asg) | — |
| POS reprint | — | — | O (own) | O `box_office.reprint` (asg) | — |
| POS void | — | — | request + approve (own) | request `box_office.void_request`; approve `box_office.void_approve`; approver ≠ requester [PROPOSED] | V |
| Cash shifts | — | — | V, verify (own) | open/close own `box_office.sell`; verify `shifts.verify` | V |
| POS settings | — | — | E (own) | — | E (platform defaults) [needs permission, LC-019] |
| Credential inventory (batches) | — | — | C V (own) | `credentials.inventory` (asg) | V |
| Credential issue/assign | — | — | O (own) | `credentials.issue` (asg) | O |
| Credential replace | — | — | O (own) | `credentials.replace` (asg) | O |
| Credential revoke | — | — | O (own) | `credentials.revoke` (asg) | O |
| Access policy, gates, zones | — | — | C E (own) | `credentials.inventory` [PROPOSED `access.configure`, DEC-16] | V E |
| Scan entry | — | — | O (own) | `access.scan_entry` (asg event, asg gate if modelled) | O |
| Scan exit | — | — | O (own) | `access.scan_exit` (asg) | O |
| Admission override | — | — | X (own), reason ≥ 5 chars, audited | X `access.override` (asg) | X |
| Access reports / live ops | — | — | V (own) | `access.reports` (asg) | V |
| Box office / sales reports | — | — | V (own) | `reports.view` (asg) | V |
| Team / staff | — | — | V C E D (own) | `team.manage` (cannot grant permissions it does not hold [PROPOSED]) | V E D, impersonate (needs permission, LC-019) |
| Payments & settlements (view) | — | — | V (own) | V `payments.view` | V (Transaction) |
| Settlement preference, KYC | — | — | E (own) **organizer-only** | — (never staff) | E (Organizer Management) |
| Fee rules, additional fees | — | — | V (own schedule) | — | C E D (Transaction) |
| Refunds | — | request [UNKNOWN DEC-07] | request [UNKNOWN DEC-07] | — | X (Transaction) |
| Transfers / reconciliation | — | — | V (own) | — | O (Transaction) |
| AI tools & credits | — | — | O (own) | O `ai.use` | E settings/packages (AI Token Management) |
| Support tickets | — | C V (own) | C V (own) | `support.manage` | V reply |
| Platform settings, CMS, languages, currencies | — | — | — | — | per admin permission |

---

## 4. Functional Module Overview and Navigation Architecture

### 4.1 Functional modules

| # | Module | Primary actors | Channels | Spec section |
|---|---|---|---|---|
| M01 | Authentication & accounts | All | All | UC-001 – UC-010 |
| M02 | Organizer onboarding & Payouts/KYC | Organizer, Admin | Organizer web, Admin | UC-011 – UC-015 |
| M03 | Event management | Organizer, Staff, Admin | Organizer web, Admin, Organizer app | **UC-016 – UC-030** |
| M04 | Tickets, variations, seat maps, passes | Organizer, Staff, Admin | Organizer web, Admin, Organizer app | UC-031 – UC-042 |
| M05 | Booking & checkout | Guest, Customer | Public web, Customer app, API | UC-043 – UC-055 |
| M06 | Payments, fees, settlement, refunds | System, Organizer, Admin | All sales channels, Admin | UC-056 – UC-070 |
| M07 | POS / Box Office | Organizer, Staff | Organizer web, Staff web | UC-071 – UC-088 |
| M08 | Team / RBAC | Organizer, Admin | Organizer web, Admin | UC-089 – UC-095 |
| M09 | Ticket issuance & delivery | System | All | UC-096 – UC-100 |
| M10 | Access credentials | Organizer, Staff | Organizer web | UC-101 – UC-110 |
| M11 | Admission & scanner | Organizer, Staff, Admin | Scanner app, Web PWA (legacy) | UC-111 – UC-125 |
| M12 | Reporting | Organizer, Staff, Admin | Web | UC-126 – UC-132 |
| M13 | AI tools & credits | Organizer, Admin | Organizer web, Admin | UC-133 – UC-137 |
| M14 | Notifications | System | Email, push | UC-138 – UC-141 |
| M15 | Admin platform management | Admin | Admin | UC-142 – UC-147 |
| M16 | Presentation (hero banners, Mobile Homepage Studio) | Admin | Admin, Customer app | UC-148 – UC-150 |
| M17 | Supplier-era modules: Shop, blog, multi-gateway, wallet/withdraw | — | Web, apps | §14 / §15 (scope decision) |

### 4.2 Organizer web navigation

**Current [VERIFIED `organizer/partials/side-navbar.blade.php`]:** Dashboard · Event Management (Events, Add Event/Choose type) · Event Bookings (Bookings, Report) · Box Office (POS, Reports) · Access Control · Team · Payments & Settlements · Payouts & KYC · Withdraw (legacy) · Transactions (legacy) · AI Tokens (Packages, History) · Support Tickets · PWA Scanner (legacy) · Profile / Change Password / Logout.

Observations [VERIFIED]: POS holds, shifts and settings are reached from inside POS/Box Office rather than the menu; there is no Customers module, no Orders module distinct from bookings, no scanner/live-operations entry except the legacy PWA and the Access console; Withdraw/Transactions (supplier wallet) sit beside Payments & Settlements (Payments V2).

**Canonical [PROPOSED]:** one operating environment grouped by job.

```
Home (Dashboard)
Events
 ├─ All events (list, filters by type/status)
 └─ Event workspace (per event, tabbed):
     Overview · Details & schedule · Tickets & passes · Access & credentials (policy, gates, zones, batches)
     · Box office (locations, POS settings) · Team on this event · Bookings · Reports · Publish
Sell
 ├─ POS (workspace) ─ Holds ─ Shifts
Orders & Customers
 ├─ Bookings ─ Customers [new] ─ Refund requests [DEC-07]
Access Operations
 ├─ Collection desk (assign/replace) ─ Live operations ─ Scan log
Team
Money
 ├─ Payments & settlements ─ Payouts & KYC ─ Fee schedule  (Withdraw/Transactions retired, LC-038)
AI Studio
Support
Account (profile, password, organization settings)
```

Staff see only the items their permissions and assignments allow; settlement preference and KYC are never visible to staff.

### 4.3 Staff web navigation [VERIFIED]
Staff Home → POS (assigned event/location) → Shifts → Change password → Logout. Staff are additionally logged into the organizer area (LC-003). **[CANONICAL]** Staff web is a scoped workspace; staff must not hold an organizer session.

### 4.4 Admin navigation [VERIFIED summary]
Dashboard · Transactions · Admin management · PWA settings · Event management (events, categories, tickets, seat maps, locations) · Event bookings (+ coupons, tax/commission, preference) · Organizer payouts · Box Office settings · Organizer workforce · Mobile Home Studio · Organizer management · Customer management · Shop management · Language · Basic settings · Mobile interface · Announcement popups · Menu builder · Home page · Payment gateways · Blog · FAQ · Custom pages · Advertise · Footer · Push notifications · AI token management · Currency · Payments finance (fee rules, additional fees, organizer payment profiles) · PWA scanner (legacy).

### 4.5 Public & customer navigation [VERIFIED]
Home · Events (list/filter by country/state/city/category) · Event details (+ seat map) · Checkout · Booking complete · Organizers · Shop (legacy) · Blog · FAQ · Contact · About · Custom pages · Customer: Dashboard, My bookings, Booking details (QR tickets), Wishlist, Support, Profile, Change password, My orders (shop, legacy).

---

## 5. Screen Registry

### 5.1 Screen ID convention
`<CHANNEL>-<MODULE>-<NNN>`: PUB public · CUS customer web · ORG organizer web · STF staff web · ADM admin · APP-CUS / APP-ORG / APP-SCN mobile apps. Status column: **C** canonical keep · **R** redesign required · **L** legacy, retire or replace · **N** new screen proposed.

### 5.2 Registry index

| Screen ID | Name | Route / location | Actor | Purpose | Status |
|---|---|---|---|---|---|
| PUB-HOME-001 | Home | `/` | Guest | Hero banners, featured events, categories | C |
| PUB-EVT-001 | Event listing | `/events` | Guest | Search/filter events | C |
| PUB-EVT-002 | Event details | `/event/{slug}/{id}` | Guest | Event info, dates, ticket selection | C |
| PUB-EVT-003 | Seat map | `/event/slot-mapping-seat` | Guest | Seat selection | C |
| PUB-CHK-001 | Checkout | `/checkout`, `/check-out2` | Guest/Customer | Customer details, payment method | R |
| PUB-CHK-002 | Booking complete | `/event-booking-complete` | Guest/Customer | Confirmation, tickets | C |
| PUB-ORG-001 | Organizers | `/organizers/` | Guest | Organizer directory | C |
| PUB-ORG-002 | Organizer profile | `/organizer/details/{id}/{name}` | Guest | Organizer events, contact | C |
| PUB-SHOP-001…005 | Shop, product, cart, checkout | `/shop/*` | Guest | Supplier merchandise shop | L (DEC-01) |
| PUB-CMS-001…005 | Blog, FAQ, contact, about, custom page | various | Guest | Content | C |
| CUS-AUTH-001 | Customer login | `/customer/login` | Customer | Login, social login | C |
| CUS-AUTH-002 | Customer signup | `/customer/signup` | Customer | Registration + email verification | C |
| CUS-AUTH-003 | Forgot / reset password | `/customer/forget-password`, `/customer/reset-password` | Customer | Recovery | C |
| CUS-DSH-001 | Customer dashboard | `/customer/dashboard` | Customer | Summary | C |
| CUS-BKG-001 | My bookings | `/customer/my-bookings` | Customer | Booking list | C |
| CUS-TKT-001 | Booking details & tickets | `/customer/booking/details/{id}` | Customer | Issued tickets with secure QR | C |
| CUS-WSH-001 | Wishlist | `/customer/wishlist` | Customer | Saved events | C |
| CUS-SUP-001…003 | Support tickets | `/customer/support-ticket*` | Customer | Support | C |
| CUS-PRF-001 | Edit profile / change password | `/customer/edit-profile` | Customer | Account | C |
| ORG-AUTH-001 | Organizer & staff login | `/organizer/login` | Organizer, staff | Login (staff `/staff/login` redirects here) | R (LC-003) |
| ORG-AUTH-002 | Organizer signup | `/organizer/signup` | Organizer | Registration | C |
| ORG-AUTH-003 | Forgot / reset password | `/organizer/forget-password` | Organizer | Recovery | C |
| ORG-AUTH-004 | Email verification | `/organizer/verify/email` | Organizer | Verify email before access | C |
| ORG-DSH-001 | Organizer dashboard | `/organizer/dashboard` | Organizer | KPIs, recent sales | R |
| ORG-EVT-001 | Event list | `/organizer/event-management/events` | Organizer, staff | Manage events | R |
| ORG-EVT-002 | Choose event type | `/organizer/choose-event-type` | Organizer, staff | Select online / venue / box office | C |
| ORG-EVT-003 | Event wizard — create | `/organizer/add-event?type=` | Organizer, staff | Create event | C |
| ORG-EVT-004 | Event wizard — edit | `/organizer/edit-event/{id}` | Organizer, staff | Edit event | C |
| ORG-EVT-005 | Ticket settings | `/organizer/edit-ticket-setting/{id}` | Organizer, staff | Ticket image, logo, instructions | R |
| ORG-EVT-006 | Event images | `/organizer/event-images/{id}` | Organizer | Gallery management | R (merge into wizard) |
| ORG-EVT-007 | Event workspace | — | Organizer, staff | Per-event hub | N |
| ORG-TKT-001 | Ticket list | `/organizer/event/ticket` | Organizer, staff | Tickets per event | C |
| ORG-TKT-002 | Ticket create | `/organizer/event/add-ticket` | Organizer, staff | New ticket | C |
| ORG-TKT-003 | Ticket edit | `/organizer/event/edit/ticket` | Organizer, staff | Edit ticket | C |
| ORG-TKT-004 | Seat map editor | `/organizer/seat-mapping/slot/*` | Organizer, staff | Slots and seats | C |
| ORG-PASS-001 | Pass products | `/organizer/events/{id}/passes` | Organizer, staff | Multi-day passes | C |
| ORG-BKG-001 | Bookings list | `/organizer/event-booking` | Organizer, staff | Search/filter bookings | R |
| ORG-BKG-002 | Booking details | `/organizer/event-booking/details/{id}` | Organizer, staff | Booking, tickets, payment | R |
| ORG-BKG-003 | Booking report / export | `/organizer/event-booking/report` | Organizer, staff | Report | C |
| ORG-POS-001 | POS workspace | `/organizer/box-office` | Organizer | 3-column selling | C |
| ORG-POS-002 | Receipt / ticket print | `/organizer/box-office/sales/{id}/print` | Organizer | Thermal print | C |
| ORG-POS-003 | POS settings | `/organizer/box-office/settings` | Organizer | Payment methods, holds, identity | C |
| ORG-POS-004 | Shifts | `/organizer/box-office/shifts` | Organizer, supervisor | Verify shifts | C |
| ORG-POS-005 | Box office reports | `/organizer/box-office/reports` | Organizer, staff | Sales reports | C |
| ORG-ACC-001 | Access control console | `/organizer/access-control` | Organizer, staff | Policy, batches, assign, replace, gates, zones, live metrics | R (split) |
| ORG-TEAM-001 | Team | `/organizer/team` | Organizer | Staff, roles, permissions, assignments | C |
| ORG-PAY-001 | Payments & settlements | `/organizer/payments-settlements` | Organizer | Effective mode, fees, preference | C |
| ORG-PAY-002 | Payouts & KYC | `/organizer/payouts-kyc` | Organizer | Razorpay linked account | C |
| ORG-PAY-003 | Withdraw list / create | `/organizer/withdraw*` | Organizer | Supplier wallet withdrawals | L (LC-038) |
| ORG-PAY-004 | Transactions | `/organizer/transaction` | Organizer | Supplier wallet ledger | L (LC-038) |
| ORG-PAY-005 | Monthly income | `/organizer/monthly-income` | Organizer | Income chart | R |
| ORG-AI-001…004 | AI packages, checkout, history, details | `/organizer/ai-token-purchase/*` | Organizer | AI credits | C |
| ORG-SUP-001…003 | Support tickets | `/organizer/support-tikcet/*` | Organizer | Support | C |
| ORG-PRF-001 | Edit profile | `/organizer/edit-profile` | Organizer | Profile | C |
| ORG-PRF-002 | Change password | `/organizer/change-password` | Organizer | Password | C |
| ORG-SCN-001 | PWA scanner | `/organizer/pwa` | Organizer | Legacy web scanner | L (LC-001) |
| STF-HOME-001 | Staff home | `/staff` | Staff | Assigned events, shortcuts | C |
| STF-POS-001 | Staff POS | `/staff/box-office` | Staff | Assignment-scoped POS | C |
| STF-POS-002 | Staff print | `/staff/box-office/sales/{id}/print` | Staff | Print | C |
| STF-SHIFT-001 | Staff shifts | `/staff/shifts` | Staff | Open/close shift | C |
| STF-PWD-001 | Staff change password | `/staff/change-password` | Staff | Forced change | C |
| ADM-AUTH-001 | Admin login | `/admin` | Admin | Login | C |
| ADM-DSH-001 | Admin dashboard | `/admin/dashboard` | Admin | Platform KPIs | C |
| ADM-EVT-001…006 | Events, choose type, create, edit, tickets, ticket settings | `/admin/*event*` | Admin | Event oversight | C |
| ADM-BKG-001…003 | Bookings, details, report | `/admin/event-booking*` | Admin | Booking oversight | C |
| ADM-CPN-001 | Coupons | `/admin/event-booking/settings/coupons` | Admin | Coupons | R (DEC-06) |
| ADM-PAY-001 | Payments finance | `/admin/payments` | Admin finance | Fee rules, additional fees | C |
| ADM-PAY-002 | Organizer payment profiles | `/admin/payments/organizers` | Admin finance | Settlement eligibility | C |
| ADM-PAY-003 | Organizer payouts & transfers | `/admin/organizer-payouts` | Admin finance | Hold days, transfers, retry | C |
| ADM-PAY-004 | Payment operations | `/admin/payments/{uuid}/*` | Admin finance | Refund, retry transfer, reconcile | C |
| ADM-BO-001 | Box office settings | `/admin/box-office-settings` | Admin | Platform POS defaults | C |
| ADM-TEAM-001 | Organizer workforce | `/admin/organizer-workforce` | Admin | Staff oversight, impersonate | C |
| ADM-ORG-001…004 | Organizers list/add/edit/details | `/admin/organizer-management/*` | Admin | Organizer management | C |
| ADM-CUS-001…003 | Customers | `/admin/customer-management/*` | Admin | Customer management | C |
| ADM-AI-001…003 | AI settings, packages, orders | `/admin/ai-token-management/*` | Admin | AI economy | C |
| ADM-HOME-001 | Mobile Homepage Studio | `/admin/mobile-home` | Admin | Campaigns/templates | C |
| ADM-SCN-001 | PWA scanner | `/admin/pwa/scanner` | Admin | Legacy scanner | L (LC-001) |
| ADM-SET-xxx | Settings, languages, currencies, CMS, gateways | `/admin/*` | Admin | Platform settings | C / L (gateways) |
| APP-CUS-001…0xx | Customer app screens | `customer-app/lib/features/*` | Customer | Discovery, checkout, bookings | R (checkout, LC-005) |
| APP-ORG-001…0xx | Organizer app screens | `organizer-app/lib/features/*` | Organizer | Events, bookings, withdraw | R (LC-025, LC-038) |
| APP-SCN-001 | Scanner login | `scanner-app/lib/auth` | Organizer/admin/staff | Role login | C |
| APP-SCN-002 | Event & gate selection | `scanner-app/lib/home` | Scanner users | Choose event, gate, direction | C |
| APP-SCN-003 | Scanner | `scanner-app/lib/scanner` | Scanner users | Camera scan, result | C |
| APP-SCN-004 | Scan history | `scanner-app/lib/history` | Scanner users | Device history | C |

Detailed specifications for event screens are in §5.3. Other screens will be detailed with their modules.

### 5.3 Event screen specifications

#### ORG-EVT-001 — Event list

| Attribute | Specification |
|---|---|
| Actor | Organizer; staff with `events.view` (canonical: assigned events only) |
| Purpose | Find, open and act on the organizer's events |
| Route | GET `/organizer/event-management/events?language=&event_type=&title=` [VERIFIED] |
| Entry points | Side nav "Events"; dashboard; post-create redirect |
| Data shown [VERIFIED] | Title (selected language), type, status (active/inactive), featured flag, links to tickets, ticket settings, edit, duplicate, delete, public page. 10 per page. |
| Data shown [PROPOSED] | Add: next date, tickets sold / capacity, gross sales, publish blockers (e.g. no tickets), lifecycle state (§7.1). |
| Filters [VERIFIED] | Title search; type filter (`online`, `venue` excludes box-office-enabled, `box_office` includes `box_office_enabled=1` venues). |
| Primary CTA | "Create event" → ORG-EVT-002 |
| Secondary actions | Edit, Duplicate, Tickets, Ticket settings, Publish/Unpublish, Feature (DEC-13), Delete (canonical: Archive, LC-022), bulk delete (canonical: bulk archive) |
| Permissions | Organizer owns; staff `events.view` / `events.manage`; ownership server-enforced in query [VERIFIED] |
| Desktop | Table with filters row, actions dropdown per row, bulk-select checkbox column |
| Tablet | Table with title, type, status, next date; actions in kebab menu; bulk actions in toolbar |
| Mobile | Card per event (thumbnail, title, type chip, status chip, next date, sold/capacity). Tap → Event workspace (ORG-EVT-007). Kebab for secondary actions. Sticky bottom "Create event" button. Filters in bottom sheet. No bulk delete on mobile. |
| Loading | Skeleton rows/cards |
| Empty | "No events yet" with Create event CTA and short explainer of three event types |
| Error | Inline banner "Couldn't load events. Retry." |
| Success | Toasts after status change/duplicate/archive |
| Previous / next | Dashboard → ORG-EVT-002 / ORG-EVT-004 / ORG-TKT-001 |
| Backend | `Organizer\EventController@index` |
| Known conflicts | LC-022 (hard delete), LC-024 (publish guard), DEC-13 (featured) |

#### ORG-EVT-002 — Choose event type

| Attribute | Specification |
|---|---|
| Actor | Organizer; staff `events.manage` |
| Purpose | Select Online, Venue or Box Office before the wizard |
| Route | GET `/organizer/choose-event-type` → links to `/organizer/add-event?type={online|venue|box_office}` [VERIFIED] |
| Data shown | Three cards with what each type requires and enables (see §6.3.1 matrix) |
| Primary CTA | Select a type card |
| Rules | Selected type is authoritative for create; `EventFormRequest` merges `type` query into `event_type` when no `event_id` [VERIFIED]. |
| Desktop / tablet | Three cards side-by-side / two-plus-one grid |
| Mobile | Stacked full-width cards, each with a one-line "best for" description |
| Error | Unknown type in URL → show chooser again [PROPOSED] |
| Next | ORG-EVT-003 |

#### ORG-EVT-003 / ORG-EVT-004 — Event wizard (create / edit)

| Attribute | Specification |
|---|---|
| Actor | Organizer; staff `events.manage` (canonical: edit only on assigned events) |
| Purpose | Create or edit an event through one shared wizard |
| Routes | Create GET `/organizer/add-event?type=`; POST `/organizer/event-store`. Edit GET `/organizer/edit-event/{id}`; POST `/organizer/event-update` [VERIFIED] |
| Steps [VERIFIED `event-wizard.js`] | 1 Type (edit-mode hides) · 2 Details · 3 Schedule · 4 Location (venue/box office) or Online access · 5 Media · 6 Publish |
| Draft behavior [VERIFIED] | Unsaved form state autosaves to the browser's `localStorage` on this device only. No server-side draft. |
| Fields | See §6.3.1 field matrix |
| Primary CTA | Step: "Next". Final: "Save event" |
| Secondary | Back, step navigator, AI content/image generation (if AI system enabled), map picker for coordinates (implementation data) |
| Validation | Client: required fields per visible step. Server: `EventFormRequest` (authoritative). |
| Desktop | Left vertical stepper, form centre, contextual help right |
| Tablet | Horizontal stepper on top, single form column |
| Mobile | Step header "Step n of 6 — Name", one section per screen, sticky bottom bar with Back / Next, language tabs collapsed into a selector, date pickers native, gallery upload via camera/library |
| Loading | Submit button spinner; disable double-submit |
| Error | Server validation errors mapped to field and step; wizard jumps to first failing step |
| Success | Create → redirect to Ticket settings (ORG-EVT-005) [VERIFIED flow continues to tickets]; Edit → stay, toast "Saved" |
| Previous / next | ORG-EVT-002 → ORG-EVT-005 → ORG-TKT-001 |
| Backend | `EventFormRequest`, `EventFormService::createEvent/updateEvent` |
| Known conflicts | LC-026, LC-027, LC-029, LC-045; local drafts are device-bound (DEC-17) |

#### ORG-EVT-005 — Ticket settings

| Attribute | Specification |
|---|---|
| Purpose | Configure the event's ticket artwork (`ticket_image`), logo (`ticket_logo`) and attendee instructions |
| Route | GET `/organizer/edit-ticket-setting/{id}`; POST `/organizer/update-ticket-setting` (JSON redirect to ticket list) [VERIFIED] |
| Rules [CANONICAL] | Only the fields `ticket_image`, `ticket_logo`, `instructions`, remove flags may be written. **Current implementation writes all request fields** (LC-023). |
| Mobile | Image pickers with preview; instructions rich text reduced to plain formatting toolbar |
| Next | ORG-TKT-001 |

#### ORG-EVT-007 — Event workspace [PROPOSED, N]
Per-event hub with tabs: Overview (state, publish blockers, sales), Details & schedule (opens wizard), Tickets & passes, Access & credentials, Box office, Team, Bookings, Reports, Publish. Mobile: tab bar becomes a horizontally scrollable segmented control; Overview first.

---

## 6. Use Case Catalogue

### 6.1 Catalogue index

| ID | Use case | Module | Status |
|---|---|---|---|
| UC-001 | Customer registration (web/app, email verification) | M01 | Reserved |
| UC-002 | Customer login (password, Google, Facebook) | M01 | Reserved |
| UC-003 | Customer password recovery | M01 | Reserved |
| UC-004 | Organizer registration & email verification | M01 | Reserved |
| UC-005 | Organizer login | M01 | Reserved |
| UC-006 | Organizer password recovery / change | M01 | Reserved |
| UC-007 | Staff login (web) & forced password change | M01 | Reserved |
| UC-008 | Staff/organizer/admin scanner login (app) | M01 | Reserved |
| UC-009 | Admin login & password recovery | M01 | Reserved |
| UC-010 | Logout & token revocation (all channels) | M01 | Reserved |
| UC-011 | Organizer profile management | M02 | Reserved |
| UC-012 | Organizer Payouts & KYC (Razorpay linked account) | M02 | Reserved |
| UC-013 | Organizer settlement preference | M02 | Reserved |
| UC-014 | Admin organizer approval / status / email status | M02 | Reserved |
| UC-015 | Admin secret login (impersonate organizer) | M02 | Reserved |
| UC-016 | View & filter event list | M03 | **Specified** |
| UC-017 | Choose event type | M03 | **Specified** |
| UC-018 | Create event (wizard) | M03 | **Specified** |
| UC-019 | Configure event schedule (single / multiple dates) | M03 | **Specified** |
| UC-020 | Configure venue location or online access | M03 | **Specified** |
| UC-021 | Configure box office locations & event re-entry | M03 | **Specified** |
| UC-022 | Manage event media & gallery (incl. AI images) | M03 | **Specified** |
| UC-023 | Configure ticket settings (artwork, logo, instructions) | M03 | **Specified** |
| UC-024 | Edit event | M03 | **Specified** |
| UC-025 | Duplicate event | M03 | **Specified** |
| UC-026 | Publish / unpublish event | M03 | **Specified** |
| UC-027 | Feature / unfeature event | M03 | **Specified** |
| UC-028 | Archive / delete event | M03 | **Specified** |
| UC-029 | Admin manages event on behalf of organizer | M03 | **Specified** |
| UC-030 | Organizer app event management | M03 | **Specified** |
| UC-031 | Create ticket (normal / free / variation) | M04 | **Specified** |
| UC-032 | Edit ticket | M04 | **Specified** |
| UC-033 | Delete / retire ticket | M04 | **Specified** |
| UC-034 | Configure ticket admission (pass type, re-entry, replacement) | M04 | **Specified** |
| UC-035 | Configure early-bird discount | M04 | **Specified** |
| UC-036 | Seat map: slots, background, seats | M04 | **Specified** |
| UC-037 | Online event ticket (auto-synced) | M04 | **Specified** |
| UC-038 | Create pass product | M04 | **Specified** |
| UC-039 | Edit / deactivate pass product | M04 | **Specified** |
| UC-040 | Pass date selection modes (all / fixed / choose-n) | M04 | **Specified** |
| UC-041 | Coupons | M04 | **Specified** |
| UC-042 | Inventory authority & stock display | M04 | **Specified** |
| UC-043 – UC-055 | Browse, details, selection, seats, checkout, free booking, Razorpay web, app/API v1, offline, confirmation, customer bookings, wishlist, cancellation/transfer | M05 | **Specified** |
| UC-056 – UC-070 | Quote, fee resolution, additional fees, tax, payment order, Razorpay order/verify, webhook, ledger, settlement routing, Route transfer, hold/release, refund, reversal, reconciliation, organizer payments view | M06 | Reserved |
| UC-071 – UC-088 | POS open, event/session/counter, cart, customer & identity, quote, payment, sale, print, email, hold, resume, cancel/expire hold, shift open/close/verify, reprint, void, settings & reports | M07 | **Specified** |
| UC-089 – UC-095 | Create staff, edit role/permissions, assignments, reset password, archive staff, admin workforce oversight, admin impersonate staff | M08 | **Specified** |
| UC-096 – UC-100 | Issue tickets, secure token, deliver email, resend/reissue, print | M09 | **Specified** |
| UC-101 – UC-110 | Access policy, batch import, collection desk assign, POS assign, replace, revoke, lost credential, online ticket → wristband exchange, gates, zones | M10 | Reserved |
| UC-111 – UC-125 | Entry, exit, re-entry, gate select, duplicate scan, concurrent scan, wrong event, wrong date, revoked credential, override, pass date check, live operations, scan log, offline policy, legacy scanner adapters | M11 | Reserved |
| UC-126 – UC-150 | Reports, AI, notifications, admin platform, presentation | M12–M16 | Reserved |

### 6.2 Use case table template
Each specified use case uses: Objective · Roles · Channel · Preconditions · Trigger · Inputs · Permissions · Success scenario · Business rules · Validations (Step / Field / Type / Rule) · Errors (Step / Condition / Message / Recovery) · Post-conditions · Notifications · Dependencies · Data entities · Backend authority · State change · Previous / Next use case · Web/Mobile differences · Audit · Security · Edge cases · Current implementation notes · Known legacy conflicts.

### 6.3 Event Management (UC-016 – UC-030)

#### 6.3.1 Event type specification

**Canonical definitions [CANONICAL/PROPOSED]**

| Aspect | Online event | Venue event | Box office event |
|---|---|---|---|
| Purpose | Attendance through a meeting/stream link | Physical venue, tickets sold online; optional counter sales | Physical venue whose primary operation is counter sales/collection, with optional online sales |
| `event_type` value | `online` | `venue` | `box_office` |
| Address, city (per language) | **Must not** be required; cleared on save [VERIFIED] | Required [VERIFIED] | Required [VERIFIED] |
| Country, state, zip | Not used | Optional [VERIFIED web]; organizer API makes country/state required by settings (LC-025) | Optional |
| Latitude / longitude | Cleared [VERIFIED] | Implementation data from map picker, never a required manual field [CANONICAL]; organizer API requires them (LC-025) | Same as venue |
| Meeting URL | Required, valid URL ≤ 2048 [VERIFIED] | Must not be required; [PROPOSED] cleared on save (currently retained if posted) | Same as venue |
| Tickets | Exactly one ticket auto-synced from the event form (price, availability, max per order, early bird) [VERIFIED `syncOnlineTicket`] | Any number of tickets, variations, seat maps, passes via ticket module | Same as venue |
| Seat maps | Not applicable [PROPOSED] | Allowed | Allowed |
| Pass products | Not applicable [PROPOSED; currently not blocked] | Allowed for multi-date events | Allowed for multi-date events |
| Dates | Single or multiple [VERIFIED] | Single or multiple | Single or multiple |
| Box office locations | Not allowed; `box_office_enabled` forced false [VERIFIED] | Allowed when `box_office_enabled` = true [VERIFIED] (DEC-02) | Always enabled; ≥ 1 active location required [VERIFIED] |
| POS availability | No | Yes, only if `box_office_enabled` | Yes |
| Access control / scanning | Not applicable; join link is the entitlement [PROPOSED]. Scanning is technically possible today. | Ticket QR scan; optional access policy and credentials | Same as venue; credentials typical |
| Event-level re-entry policy | Forced `none` [VERIFIED] | Stored only when `box_office_enabled` [VERIFIED]; ignored by admission engine (LC-013) | Stored; ignored by admission engine (LC-013) |
| Sales channels | web, mobile | web, mobile, POS (if enabled) | web, mobile, POS |
| Fee rule event-type key | `online` | `venue` | `box_office` [VERIFIED resolver uses `events.event_type`] |
| Type change after bookings | Forbidden [VERIFIED] | Forbidden | Forbidden |
| Type change before bookings | [PROPOSED] Forbidden after first ticket is created; otherwise allowed with a warning (LC-026) | Same | Same |

**Field matrix — event wizard [VERIFIED `EventFormRequest`]**

| Step | Field | Online | Venue | Box office | Rule |
|---|---|---|---|---|---|
| 1 Type | event_type | R | R | R | in: venue, online, box_office; from `?type=` on create |
| 2 Details | {lang}_title | R | R | R | string ≤ 255, every active language |
| 2 Details | {lang}_category_id | R | R | R | exists `event_categories` |
| 2 Details | {lang}_description | R | R | R | 30–1200 chars, HTML purified |
| 2 Details | {lang}_refund_policy, meta keywords/description | O | O | O | string |
| 3 Schedule | date_type | R | R | R | single / multiple |
| 3 Schedule | start_date, start_time, end_date, end_time | R if single | R if single | R if single | end > start |
| 3 Schedule | m_start_date[], m_start_time[], m_end_date[], m_end_time[] | R if multiple (≥1 row) | same | same | each end > start |
| 3 Schedule | countdown_status | R | R | R | 0/1 (wizard radio) |
| 4 Location | {lang}_address | — | R | R | ≤ 500 |
| 4 Location | {lang}_city | — | R | R | integer id |
| 4 Location | {lang}_country, _state, _zip_code | — | O | O | |
| 4 Location | latitude, longitude | — | O | O | numeric ranges |
| 4 Online | meeting_url | R | — | — | url |
| 4 Online | price, pricing_type, ticket_available_type, ticket_available, max_ticket_buy_type, max_buy_ticket, early bird fields | R (types) | — | — | ints ≥ 1 where limited |
| 4 Box office | box_office_enabled | — | O | forced true | boolean |
| 4 Box office | box_office_locations[].name/.address/.active | — | R if enabled | R (≥ 1) | name ≤ 255, address ≤ 500 |
| 4 Box office | reentry_policy, max_reentries | — | R if enabled | R | none/unlimited/limited; max ≥ 1 if limited |
| 5 Media | thumbnail | R on create (or AI `thumbnail_image_url`) | same | same | jpg/png ≤ 1 MB, exactly 320×230 |
| 5 Media | slider_images[] | R on create | same | same | ids of uploaded `event_images` |
| 6 Publish | status | R | R | R | currently any value (LC-029); canonical 0/1 then lifecycle |
| 6 Publish | is_featured | R | R | R | yes/no (DEC-13) |
| Admin only | organizer_id | O | O | O | exists organizers; prohibited for organizers |

R required · O optional · — not applicable / must not be required.

#### 6.3.2 Single vs multiple date semantics

| Rule | Status |
|---|---|
| `date_type = single`: dates live on `events.start_date/start_time/end_date/end_time`; `event_dates` rows for the event are deleted on save. | [VERIFIED] |
| `date_type = multiple`: event-level date fields are nulled; each session is an `event_dates` row; `events.end_date_time` = latest session end; `events.duration` = first session duration. | [VERIFIED] |
| On edit, multiple-date rows not present in the submission are deleted. | [VERIFIED] — conflicts with pass entitlements and bookings that reference dates (LC-027) |
| A single-date event must never expose date choice in checkout, POS or passes. | [CANONICAL] |
| Pass products require `date_type = multiple`. | [PROPOSED] |
| A session that has bookings, issued tickets, pass entitlements or admission states cannot be deleted; it may be cancelled (DEC-04). | [PROPOSED] |
| Bookings store the chosen date as text `bookings.event_date`; canonical target is `event_date_id`. | [VERIFIED current] / [PROPOSED target] |
| Switching single ↔ multiple after sales is forbidden. | [PROPOSED] |

---

#### UC-016 View & filter event list

| Field | Specification |
|---|---|
| Objective | Organizer (or permitted staff) finds and acts on owned events. |
| Roles | Organizer; staff `events.view` |
| Channel | Organizer Web (ORG-EVT-001); Organizer App (UC-030) |
| Preconditions | Authenticated organizer, email verified (`EmailStatus`), account active (`Deactive`). |
| Trigger | Select "Events" in navigation, or redirect after event actions. |
| Inputs | language (required), event_type (optional), title (optional), page |
| Success scenario | 1. User opens Events. 2. System resolves the language (404 if unknown code [VERIFIED]). 3. System queries events owned by the authenticated organizer joined with content in that language. 4. Type filter applied: `box_office` = type box_office OR box_office_enabled; `venue` = venue without box office flag; `online`. 5. Title LIKE filter applied. 6. Results paginate 10 per page. 7. User picks an action (edit → UC-024, duplicate → UC-025, tickets → UC-031, ticket settings → UC-023, status → UC-026, delete → UC-028). |
| Business rules | Only owned events are listed [VERIFIED]. [CANONICAL] Staff see only assigned events. [PROPOSED] Show lifecycle state and publish blockers. |
| Validations | language: must exist; event_type ∈ {online, venue, box_office} or empty [PROPOSED; currently unvalidated]. |
| Errors | Unknown language → 404 [VERIFIED]; [PROPOSED] fall back to default language. |
| Post-conditions | None (read-only). |
| Notifications | None. |
| Data entities | events, event_contents, languages |
| Backend authority | `Organizer\EventController@index` |
| Previous / Next | Dashboard / UC-017, UC-024, UC-025, UC-026, UC-028, UC-031 |
| Web/Mobile | Web paginated table; app list with type tabs (online/venue only — box office missing in app, LC-025). |
| Audit | None. |
| Security | Ownership in query [VERIFIED]. Staff assignment scope not applied (LC-003). |
| Edge cases | Event with no content row in selected language is omitted from list [VERIFIED by inner join] — [PROPOSED] show with "untranslated" badge. |
| Current notes | View links to an admin route for one control [VERIFIED `index.blade.php` references `admin.event_management.event`] — verify it is not rendered for organizers. |
| Legacy conflicts | LC-003, LC-022, LC-025 |

#### UC-017 Choose event type

| Field | Specification |
|---|---|
| Objective | Select the event type that drives required fields, sales channels and operations. |
| Roles | Organizer; staff `events.manage` |
| Channel | Organizer Web (ORG-EVT-002); Admin (ADM-EVT); Organizer App (online/venue only) |
| Preconditions | Authenticated; may create events. |
| Trigger | "Create event". |
| Success scenario | 1. System shows Online, Venue, Box Office cards with requirements summary. 2. User selects one. 3. System opens the wizard with `?type=` (UC-018). |
| Business rules | The type chosen here is authoritative for creation [VERIFIED `prepareForValidation`]. Type cannot change after bookings exist [VERIFIED]. [PROPOSED] Type locked once a ticket exists. |
| Validations | type ∈ {online, venue, box_office}. |
| Errors | Invalid type → stay on chooser [PROPOSED]. |
| Post-conditions | None. |
| Next | UC-018 |
| Web/Mobile | Organizer app offers only "Add online event" / "Add venue event" [VERIFIED] (LC-025). |
| Legacy conflicts | LC-025, DEC-02 |

#### UC-018 Create event (wizard)

| Field | Specification |
|---|---|
| Objective | Create an event with valid details, schedule, location/online access, box office configuration and media in one transaction. |
| Roles | Organizer; staff `events.manage`; Admin (UC-029) |
| Channel | Organizer Web (ORG-EVT-003); Admin; Organizer App (UC-030, divergent) |
| Preconditions | UC-017 completed; at least one active language and category exist; gallery images uploaded via UC-022 before submit. |
| Trigger | "Save event" on final step. |
| Inputs | §6.3.1 field matrix |
| Permissions | `auth:organizer`, `Deactive`, `EmailStatus`, `organizer.staff.rbac` (route prefix `organizer.event_management.*` → `events.manage` for POST) [VERIFIED] |
| Success scenario | 1. User completes steps Details → Schedule → Location/Online → Media → Publish; the browser autosaves the draft locally. 2. User submits. 3. `EventFormRequest` validates (§6.3.1) including end-after-start per session. 4. `EventFormService::createEvent` opens a DB transaction. 5. organizer_id is forced to the authenticated organizer. 6. Data normalized: single dates compute duration/end_date_time; multiple nulls event-level dates; box_office type forces `box_office_enabled`; re-entry fields cleared when not enabled; online clears coordinates and box office. 7. Thumbnail stored (upload, or AI image resized to 320×230 JPEG). 8. Event row created. 9. Dates synced (multiple → `event_dates`). 10. Content rows created for every language (slug from title; address fields only for venue/box office). 11. Online: single ticket upserted from form. 12. Box office locations upserted by name. 13. Uploaded gallery images attached. 14. Transaction commits. 15. User is taken to Ticket settings (UC-023) and then Tickets (UC-031). |
| Business rules | BR-EVT-01 One canonical service for create/edit/duplicate [CANONICAL]. BR-EVT-02 Server ignores client organizer_id for organizers [VERIFIED]. BR-EVT-03 Online events never carry physical location or box office [VERIFIED]. BR-EVT-04 Box office events require ≥ 1 location [VERIFIED]. BR-EVT-05 New events start unpublished (Draft) regardless of submitted status [PROPOSED; currently status is taken from form — LC-024/LC-029]. BR-EVT-06 Venue coordinates are optional implementation data [CANONICAL]. |
| Validations | Step / Field / Type / Rule: 2 title / string / required ≤ 255 per language · 2 category / id / exists · 2 description / text / 30–1200 · 3 date_type / enum / single|multiple · 3 end / datetime / after start · 4 address, city / string,id / required for venue & box office · 4 meeting_url / url / required for online · 4 box_office_locations / array / ≥ 1 when enabled · 4 max_reentries / int / ≥ 1 when limited · 5 thumbnail / image / jpg/png ≤ 1 MB exactly 320×230 unless AI url · 5 slider_images / ids / required on create. |
| Errors | Validation → 422 with field messages; wizard returns to the first failing step [PROPOSED; verify current JS]. AI thumbnail unusable → "Generated thumbnail could not be prepared/processed/saved." [VERIFIED]. Any exception → transaction rolled back; [PROPOSED] uploaded thumbnail file removed. |
| Post-conditions | Event exists (status per BR-EVT-05), content per language, dates, online ticket (if online), box office locations (if enabled), gallery attached. |
| Notifications | None [VERIFIED]. [PROPOSED] none required. |
| Dependencies | Languages, categories, countries/states/cities, AI system (optional), gallery upload (UC-022). |
| Data entities | events, event_contents, event_dates, tickets (online), box_office_locations, event_images |
| Backend authority | `EventFormRequest`, `EventFormService::createEvent` |
| State change | ∅ → Draft (canonical) / status as submitted (current) |
| Previous / Next | UC-017 / UC-023 → UC-031 |
| Web/Mobile | Mobile web uses the same wizard; organizer app uses a separate API with different rules (LC-025). |
| Audit | [PROPOSED] Record event_created with actor type (organizer/staff/admin) and id. Not recorded today. |
| Security | Description HTML purified [VERIFIED]. Gallery attach accepts any unattached image id (LC-045). Staff edits are attributed to the organizer (LC-003). |
| Edge cases | Two languages with same title → slugs collide [PROPOSED: append id]. Multiple-date rows submitted out of order — service computes latest end correctly [VERIFIED]. Browser-local draft not available on another device [VERIFIED]. |
| Current notes | Thumbnail exact-dimension rule is strict; AI path resizes automatically. |
| Legacy conflicts | LC-024, LC-025, LC-029, LC-045 |

#### UC-019 Configure event schedule

| Field | Specification |
|---|---|
| Objective | Define when the event happens with unambiguous single or multi-session semantics. |
| Roles | Organizer; staff `events.manage`; Admin |
| Channel | Wizard step 3 (create/edit); Organizer App |
| Preconditions | In UC-018 or UC-024. |
| Success scenario | 1. User chooses Single or Multiple. 2. Single: one start and end date/time. 3. Multiple: one row per session (add/remove rows); each existing row keeps its `date_ids[i]`. 4. Optional countdown toggle. 5. On save, rules in §6.3.2 apply. |
| Business rules | §6.3.2. [PROPOSED] Sessions with commercial or admission history cannot be removed; they can be cancelled (DEC-04). [PROPOSED] Past sessions cannot be added to a new event except by duplication, where they must be shifted or confirmed (UC-025). |
| Validations | end after start per row [VERIFIED]; date_ids belong to this event [VERIFIED via `where event_id`]; ≥ 1 row for multiple [VERIFIED]. |
| Errors | "Event end must be after the start." [VERIFIED]. [PROPOSED] "This session has bookings and cannot be removed." |
| Post-conditions | events date fields or event_dates rows updated. |
| Data entities | events, event_dates (referenced by event_pass_dates, pass_entitlements, ticket_admission_states, access_scans) |
| Backend authority | `EventFormService::syncDates` |
| Organizer App | Separate endpoint `event-delete-date` deletes a date directly [VERIFIED route] (LC-025, LC-027). |
| Edge cases | Timezone: dates stored as local strings without timezone [VERIFIED schema varchar] — DEC-18. Overlapping sessions allowed [VERIFIED]. |
| Legacy conflicts | LC-027 |

#### UC-020 Configure venue location or online access

| Field | Specification |
|---|---|
| Objective | Capture where (venue/box office) or how (online) attendees join. |
| Roles | Organizer; staff `events.manage`; Admin |
| Channel | Wizard step 4 |
| Success scenario | Venue/box office: 1. User enters address per language and selects country/state/city (searchable, India locations seeded). 2. Optional map pin sets latitude/longitude. Online: 1. User enters meeting URL. 2. User sets ticket price/pricing type, availability, max per order and early bird for the auto-managed online ticket. |
| Business rules | Online join link is delivered only to entitled customers [PROPOSED; verify booking emails/views]. [CANONICAL] Coordinates are not a required manual input. [PROPOSED] Meeting URL is never shown on public pages. |
| Validations | §6.3.1 rows 4. |
| Errors | "The meeting url field is required when event type is online." (Laravel default) [VERIFIED rule]. |
| Post-conditions | Location in event_contents; meeting_url on events; online ticket synced. |
| Backend authority | `EventFormService::syncContents`, `syncOnlineTicket` |
| Edge cases | Online ticket upsert uses `firstOrNew(['event_id'])` — if other tickets exist (e.g. after type change) the first is overwritten (LC-026). |
| Legacy conflicts | LC-025, LC-026 |

#### UC-021 Configure box office locations & event re-entry

| Field | Specification |
|---|---|
| Objective | Enable counter sales locations and default re-entry behavior. |
| Roles | Organizer; staff `events.manage`; Admin |
| Channel | Wizard step 4 |
| Preconditions | Event type `box_office`, or `venue` with box office enabled. |
| Success scenario | 1. User adds one or more locations (name, address, active). 2. User selects re-entry policy none/unlimited/limited and max re-entries if limited. 3. On save, locations are upserted by name; locations not submitted are deleted [VERIFIED]. |
| Business rules | Location names unique per event [VERIFIED upsert key]. [PROPOSED] Locations with sales, shifts, holds or staff assignments are deactivated, not deleted (LC-028 related). [CANONICAL] One re-entry authority (DEC-05): event access policy or ticket configuration; event-level fields become defaults copied into the access policy. |
| Validations | name ≤ 255 required, address ≤ 500 required, ≥ 1 when enabled [VERIFIED]. |
| Errors | Laravel validation messages. |
| Post-conditions | box_office_locations rows; events.box_office_enabled, reentry_policy, max_reentries. |
| Backend authority | `EventFormService::normalizeEventData`, `syncBoxOfficeLocations` |
| Dependencies | POS (UC-071+), staff assignments (UC-091), shifts. |
| Edge cases | Renaming a location creates a new row and deletes the old one, orphaning sales/shift/assignment references [VERIFIED behavior of name-keyed upsert + delete]. |
| Legacy conflicts | LC-013, DEC-02, DEC-05 |

#### UC-022 Manage event media & gallery

| Field | Specification |
|---|---|
| Objective | Provide thumbnail and gallery/slider images, optionally AI-generated. |
| Roles | Organizer; staff `events.manage`; Admin |
| Channel | Wizard step 5; ORG-EVT-006 |
| Success scenario | 1. User drops gallery images (dropzone) → each uploaded immediately to `event-imagesstore` and returns an image id. 2. User removes an image → `event-imagermv` (unsaved) or `event-img-dbrmv` (saved; last image cannot be removed [VERIFIED count check]). 3. User uploads a 320×230 thumbnail or generates one with AI (`/organizer/ai/generate-slider-images`, `/ai/generate/image`) consuming AI credits. 4. On save, ids attach to the event. |
| Validations | gallery file jpg/jpeg/png ≤ 1 MB [VERIFIED]; thumbnail rules §6.3.1. AI generation requires AI system enabled and quota [VERIFIED middleware]. |
| Errors | Upload too large/wrong type → field error. AI quota exhausted → quota middleware error. |
| Post-conditions | event_images rows linked; files in `public/assets/admin/img/event-gallery/`. |
| Security | Image remove endpoints and attach-by-id do not check ownership (LC-045). AI routes are outside staff RBAC (LC-003). |
| Edge cases | Abandoned uploads remain as unattached `event_images` rows and files [VERIFIED] — [PROPOSED] scheduled cleanup. |
| Legacy conflicts | LC-003, LC-045 |

#### UC-023 Configure ticket settings

| Field | Specification |
|---|---|
| Objective | Configure the visual ticket (artwork, logo) and attendee instructions printed/emailed with issued tickets. |
| Roles | Organizer; staff `tickets.manage` [VERIFIED route mapping `ticket_setting` → tickets.manage]; Admin |
| Channel | Organizer Web (ORG-EVT-005); Admin; Organizer App |
| Preconditions | Event exists and is owned. |
| Success scenario | 1. User opens ticket settings. 2. Uploads/replaces/removes ticket image and logo, edits instructions. 3. System validates image MIME, purifies instructions, stores files. 4. System updates the event and returns a redirect to the ticket list. |
| Business rules | [CANONICAL] Only ticket presentation fields may be changed here. Ticket artwork applies to all delivery channels (email, print, customer view) [PROPOSED; verify print/email templates]. |
| Validations | ticket_image, ticket_logo: valid image MIME [VERIFIED]. [PROPOSED] size limits and recommended dimensions. |
| Errors | Event not owned → 404 [VERIFIED]. |
| Post-conditions | events.ticket_image, ticket_logo, instructions updated. |
| Backend authority | `Organizer\EventController@updateTicketSetting` |
| Security | **Current implementation mass-assigns the full request** to the Event model whose fillable list includes `status`, `organizer_id`, `event_type`, `box_office_enabled`, `reentry_policy` (LC-023). |
| Next | UC-031 |
| Legacy conflicts | LC-023 |

#### UC-024 Edit event

| Field | Specification |
|---|---|
| Objective | Change event information without breaking sales, entitlements or admission history. |
| Roles | Organizer; staff `events.manage` (assigned events); Admin |
| Channel | Organizer Web (ORG-EVT-004); Admin; Organizer App |
| Preconditions | Event owned (`EventActor::assertOwns` [VERIFIED]). |
| Success scenario | Same wizard as UC-018 without the Type step. On submit: validation (thumbnail and gallery optional), ownership check, transaction, normalization, thumbnail replacement (old file deleted), event update, date sync with deletions, content sync, online ticket sync, box office location sync. |
| Business rules | BR-EVT-07 Event type immutable after bookings [VERIFIED]; [PROPOSED] after first ticket. BR-EVT-08 Edit never changes organizer for organizer actors [VERIFIED]. BR-EVT-09 [PROPOSED] Edits to dates, location or meeting URL of a published event with sales notify booked customers (DEC-09). BR-EVT-10 [PROPOSED] Protected sessions/locations cannot be deleted (UC-019, UC-021). |
| Validations | As UC-018; plus "Event type cannot be changed after bookings exist." [VERIFIED]. |
| Errors | Not owner → 403 [VERIFIED]. Validation → field errors. |
| Post-conditions | Event updated; removed dates/locations deleted (current). |
| Audit | [PROPOSED] event_updated with changed-field list. |
| Previous / Next | UC-016 / UC-031, UC-026 |
| Web/Mobile | Organizer App update endpoint does not use the service (LC-025). |
| Edge cases | Changing single → multiple deletes nothing but nulls event-level dates; bookings with text `event_date` keep the old date string [VERIFIED schema]. |
| Legacy conflicts | LC-026, LC-027, LC-025 |

#### UC-025 Duplicate event

| Field | Specification |
|---|---|
| Objective | Create a new unpublished event from an existing one to save setup time. |
| Roles | Organizer; staff `events.manage`; Admin |
| Channel | Organizer Web (event list action); Admin |
| Preconditions | Source event owned. |
| Trigger | POST `/organizer/duplicate-event/{id}`. |
| Success scenario | 1. Ownership checked. 2. Transaction: event replicated with status 0 and featured "no"; thumbnail, ticket image, logo, slot image files copied. 3. Content rows copied with " - Copy" title suffix and new slug; Google calendar id cleared. 4. Dates copied. 5. Gallery copied with new files. 6. Tickets copied with seat-slot identifiers stripped and slot flags disabled. 7. On failure, copied files are deleted. 8. User lands on the edit wizard of the copy [PROPOSED; verify redirect]. |
| Business rules | [CANONICAL] Duplicate is an operation on the canonical event model. [PROPOSED] Copy: box office locations, access policy, gates, zones, event additional fees, pass products (unlinked from dates until remapped). Never copy: bookings, issued tickets, credentials, sales, shifts, staff assignments, sold quantities. Ticket inventory resets to the configured original capacity (DEC-08). Past dates must be shifted or confirmed before publish. |
| Validations | Source exists and owned. |
| Errors | Asset copy failure → "Unable to duplicate event media asset." and rollback [VERIFIED]. |
| Post-conditions | New event (Draft) with copied configuration. |
| Data entities | events, event_contents, event_dates, event_images, tickets |
| Backend authority | `EventFormService::duplicateEvent` |
| Audit | [PROPOSED] event_duplicated with source id. |
| Edge cases | `ticket_available` is copied as the **remaining** stock of the source [VERIFIED: stock is decremented in place at finalization] — copy may start with 0 availability (LC-028). Passes not copied, so multi-day setup is lost (LC-028). |
| Legacy conflicts | LC-028 |

#### UC-026 Publish / unpublish event

| Field | Specification |
|---|---|
| Objective | Control whether an event is visible and sellable. |
| Roles | Organizer; staff `events.manage` [PROPOSED `events.publish`, DEC-14]; Admin |
| Channel | Organizer Web (list toggle), wizard step 6; Admin; Organizer App (`event-update-status`) |
| Preconditions | Event owned. |
| Success scenario (current) [VERIFIED] | 1. User sets status 1 (active) or 0 (inactive). 2. If activating and the event has any paid ticket/variation and organizer KYC is not `activated`, the system blocks with "Complete Payouts & KYC before publishing an event with paid tickets. Free events can be published immediately." 3. Otherwise status saved. |
| Success scenario (canonical) [PROPOSED] | 1. User clicks Publish. 2. System evaluates publish readiness: ≥ 1 active sellable ticket or pass; ≥ 1 future date; box office locations if box office; meeting URL if online; fee schedule resolvable; access policy valid if credentials required. 3. Settlement eligibility is **not** a blocker; sales route to BookTKIT Managed when Direct is ineligible (Payments V2) — unless the Product Owner decides otherwise (DEC-03). 4. Event moves Draft → Published. 5. Unpublish moves Published → Paused (sales stop; existing tickets remain valid). |
| Business rules | One publish rule applied on all channels and on create. [CANONICAL] Unpublishing never invalidates issued tickets. |
| Validations | status ∈ {0,1} [PROPOSED; currently unvalidated]. |
| Errors | Event not found → PHP error (null dereference) [VERIFIED code path]; non-owner → silent redirect back [VERIFIED]. [PROPOSED] 404/403 with message. Readiness failure → list of blockers. |
| Post-conditions | events.status changed. |
| Notifications | [PROPOSED] none to customers; optional organizer confirmation. |
| Audit | [PROPOSED] event_published / event_unpublished with actor. |
| Security | Guard bypassable: create/edit submit `status` directly, ticket settings mass-assignment, organizer API status endpoint (LC-024, LC-023, LC-025). |
| Next | Sales (UC-043+, UC-071+) |
| Legacy conflicts | LC-024, LC-029, DEC-03 |

#### UC-027 Feature / unfeature event

| Field | Specification |
|---|---|
| Objective | Mark an event as featured for homepage/discovery placement. |
| Roles | Current: Organizer and Admin [VERIFIED]. Canonical: [UNKNOWN DEC-13] (platform merchandising decision). |
| Channel | Organizer Web, Admin, Organizer App |
| Success scenario | Toggle `is_featured` yes/no. |
| Business rules | [PROPOSED] Featuring is an admin/platform decision or a paid placement; organizers may request it. |
| Post-conditions | events.is_featured updated; homepage/app featured lists change. |
| Legacy conflicts | DEC-13 |

#### UC-028 Archive / delete event

| Field | Specification |
|---|---|
| Objective | Remove an event from operation without losing commercial, financial or admission history. |
| Roles | Organizer; staff `events.manage`; Admin |
| Channel | Organizer Web (single and bulk), Admin, Organizer App |
| Current behavior [VERIFIED] | Hard delete: thumbnail file, content rows, gallery files/rows, **all bookings with their invoices/attachments**, tickets, wishlists, then the event. No check for sales, payments, issued tickets or settlements. |
| Canonical behavior [PROPOSED] | 1. If the event has no bookings, payment orders, POS sales, issued tickets or credentials: hard delete allowed (draft cleanup). 2. Otherwise only **Archive**: event hidden, sales closed, configuration read-only; all bookings, payments, ledger, tickets, credentials, scans retained. 3. Cancelling an event with sales is a separate flow (DEC-04) that triggers refunds per policy. |
| Validations | Owner; dependency check above. |
| Errors | [PROPOSED] "This event has sales and can only be archived." |
| Post-conditions | Draft deleted or event archived. |
| Audit | Mandatory record of deletion/archive with actor and reason [PROPOSED]. |
| Security | Bulk delete endpoint [VERIFIED route] — verify ownership per id (same pattern as LC-021). |
| Legacy conflicts | LC-022 |

#### UC-029 Admin manages event on behalf of organizer

| Field | Specification |
|---|---|
| Objective | Platform operations can create, edit, duplicate, publish and remove any event. |
| Roles | Admin with `Event Management` permission |
| Channel | Admin (ADM-EVT-001…006) |
| Success scenario | As UC-018/UC-024/UC-025/UC-026 with `EventActor::admin()`; admin may choose `organizer_id` or leave it empty (platform-owned event) [VERIFIED]. |
| Business rules | [CANONICAL] Same domain service and rules as organizer. [PROPOSED] Admin publish follows the same readiness rules; admin overrides are audited. [UNKNOWN DEC-19] Whether platform-owned events (organizer_id null) remain supported, and how they settle. |
| Audit | [PROPOSED] admin_id on every admin event mutation. |
| Legacy conflicts | DEC-19 |

#### UC-030 Organizer app event management

| Field | Specification |
|---|---|
| Objective | Organizer manages events from the Flutter organizer app with the same rules as web. |
| Roles | Organizer (Sanctum) |
| Channel | Organizer App → `/api/organizer/event-management/*` |
| Current behavior [VERIFIED] | Separate controller (`Api\Organizer\EventController`, 1,409 lines) with its own validation: no `box_office` type; venue requires latitude/longitude; country/state required by platform settings; separate date deletion endpoint; status update without the KYC publish guard; does not use `EventFormService`. Location lookup endpoints are public (`withoutMiddleware`). |
| Canonical behavior [CANONICAL] | API endpoints are thin adapters over `EventFormRequest` rules and `EventFormService`; same field matrix §6.3.1, same publish rules UC-026, same protections UC-019/UC-021/UC-028. Additive versioned endpoints (`/api/v1/organizer/events`) preferred over changing legacy JSON. |
| Migration | Phase 1: route legacy store/update through the service with request mapping. Phase 2: new v1 endpoints + app update. Phase 3: retire legacy endpoints after app adoption. |
| Legacy conflicts | LC-025 |

---

### 6.4 Ticket, Variation, Seat Map & Pass Management (UC-031 – UC-042)

#### 6.4.1 Commercial product model

A **ticket type** (`tickets`) and a **pass product** (`event_pass_products`) are the two sellable products. Both are commercial entitlements; neither is a credential (§6.10).

| Attribute | Ticket type — current [VERIFIED] | Pass product — current [VERIFIED] | Canonical [CANONICAL/PROPOSED] |
|---|---|---|---|
| Identity | `tickets.id`; title/description per language in `ticket_contents` | `uuid`, `code` unique per event, `name` (single language) | Stable id + localized name for both |
| Pricing | `pricing_type` normal / free / variation; `price` (rupees as string), `f_price` (display), variation prices in JSON | `price` integer paise | All prices stored in minor units [PROPOSED] |
| Variations | JSON array keyed by **name** (default language); per-variation price, availability, max per order; translated names in `variation_contents` | n/a | Variations as child rows with stable ids [PROPOSED, DEC-23] |
| Inventory | `ticket_available_type` unlimited/limited; `ticket_available` is **remaining** stock, decremented in place | `inventory_type`, `inventory_quantity` (capacity), `sold_quantity` | Capacity + sold counter for both (DEC-08) |
| Inventory scope | Shared across **all dates** of a multi-date event | Pass consumes its own stock **and** the linked base ticket's stock | Per-date inventory for date-bound tickets (DEC-24); pass stock rule decided by DEC-25 |
| Max per order | `max_ticket_buy_type`, `max_buy_ticket` (and per variation) — **not enforced** by Payments V2 quote or POS (LC-050) | `max_per_order` 1–50, enforced | Enforced server-side on every channel |
| Sales window | None (only early-bird deadline) | None | `sales_start_at`, `sales_end_at` per product [PROPOSED, DEC-26] |
| Sales channels | None — every ticket sells on every channel | `sales_channels` ⊆ {web, mobile, pos, box_office} | Channel availability on both; one vocabulary (`web`, `mobile`, `pos`) (LC-016) |
| Date applicability | None — booking stores free-text `event_date` | Explicit `event_pass_dates`; selection mode fixed/all/choose-n | Ticket either "any single date chosen at purchase" or "fixed date(s)" [PROPOSED, DEC-24] |
| Discounts | Early bird (fixed per ticket or percentage per line) | None | Early bird + coupon/discount rules evaluated in the quote (DEC-06) |
| Admission config | `admission_pass_type`, `collection_required`, `allow_mobile_qr_before_assignment`, `exit_scan_required`, `reentry_policy`, `max_reentries`, `replacement_allowed`, `max_replacements`, `replacement_fee_paise` | `credential_types`, `admissions_per_holder` — **both unused by admission** (LC-054) | One admission configuration per product, honored by the engine |
| Seat map | Optional slot/seat map per ticket or variation (`slots`, `slot_seats`, `slot_images`) | n/a | Seat selection priced and reserved by the server (LC-048) |
| Status | None (exists = sellable) | `active` boolean | `active` / `hidden` / `sold_out` (derived) / `archived` for both [PROPOSED] |
| Deletion | Hard delete, no checks; cascades to pass products and their entitlements (LC-047) | Delete only if `sold_quantity = 0`; otherwise disable [VERIFIED] | Same rule as passes for tickets |

**Inventory authority [CANONICAL]:** the server is the only authority for availability. Client quantities are requests. Today stock is decremented at three points [VERIFIED]: `BookingFinalizationService` (web/mobile after payment), `LockedTicketInventoryService::reserve` (POS sale), `EventPassService::consume` (passes, both channels); restored only by POS void (`LockedTicketInventoryService::restore`). Online refunds/cancellations do not restore stock [VERIFIED: no restore call outside POS void].

#### 6.4.2 Admission pass type vocabulary

| Value | Ticket form | Access policy | Credential batch | Pass credential_types | Canonical meaning |
|---|---|---|---|---|---|
| mobile_qr | ✓ (default) | — | — | digital_qr / printed_qr | Secure BookTKIT ticket QR, no physical credential |
| qr_wristband | ✓ | ✓ | ✓ | ✓ | Printed QR wristband |
| rfid_wristband | ✓ | ✓ | **✗** | ✓ | RFID wristband |
| rfid_card | ✓ | ✓ | ✓ | ✓ | RFID card |
| nfc_wristband | ✓ | ✓ | ✓ | ✓ | NFC wristband (docs call it "NFC card") |
| qr_badge | ✓ | ✓ | ✓ | ✓ | QR badge |
| physical_id | ✓ | ✓ | ✓ | ✓ | Physical ID/pass |

[VERIFIED] Vocabularies differ between forms; `rfid_wristband` tickets cannot be stocked because batches reject the type; credential assignment does not check that the credential type matches the ticket's `admission_pass_type` (LC-053). [CANONICAL] One enumeration shared by ticket, policy, batch, pass and assignment validation.

#### 6.4.3 Admission configuration precedence [VERIFIED `AccessControlService`]
If the ticket type has a non-empty `admission_pass_type`, ticket settings are used and the event access policy is ignored for that ticket. Because the column defaults to `mobile_qr`, **every ticket created after the migration is "configured"**, so the event access policy's re-entry, exit and QR-before-assignment settings never apply to them (LC-049). Event-level `reentry_policy` is never used (LC-013). Resolution: DEC-05.

---

#### UC-031 Create ticket

| Field | Specification |
|---|---|
| Objective | Define a sellable ticket type for a venue or box office event. |
| Roles | Organizer; staff `tickets.manage`; Admin |
| Channel | Organizer Web (ORG-TKT-002); Admin; Organizer App (`/api/organizer/event-management/event/store-ticket`) |
| Preconditions | Event exists and is owned by the actor [CANONICAL — **not enforced on web**, LC-020]; event type venue or box_office (online uses UC-037). |
| Trigger | "Add ticket" from ticket list (ORG-TKT-001). |
| Inputs | Per language: title (required), description. Pricing: `pricing_type_2` free / normal / variation; price; variations (name per language, price, availability type/qty, max per order type/qty). Availability type/qty; max per order type/qty. Early bird (UC-035). Admission settings (UC-034). |
| Success scenario | 1. User opens ticket create for an event. 2. Chooses pricing type. 3. Free: price 0. Normal: price ≥ 0. Variation: one row per variation with price ≥ 1. 4. Sets availability and max per order. 5. Optionally early bird and admission settings. 6. Submits. 7. `TicketRequest` validates. 8. Admission settings normalized (physical types allow collection/replacement; mobile QR forces QR-before-assignment true). 9. Ticket row created with random slot identifiers for future seat maps. 10. Variation translated names stored in `variation_contents`; title/description in `ticket_contents`. 11. User returns to the ticket list; may open the seat map (UC-036). |
| Business rules | BR-TKT-01 Ticket belongs to exactly one event; actor must own it [CANONICAL]. BR-TKT-02 Free variation prices are not allowed (min 1) [VERIFIED] — a free tier needs a separate free ticket. BR-TKT-03 Variation identity is its default-language name [VERIFIED]; names must be unique within a ticket [PROPOSED]. BR-TKT-04 Online events cannot have additional tickets [PROPOSED]. BR-TKT-05 Paid tickets on an unpublished or published event do not depend on KYC (DEC-03). |
| Validations | Step / Field / Type / Rule: 2 pricing_type_2 / enum / required · 3 price / numeric / ≥ 0 when normal · 3 variation_name.* / string / required · 3 variation_price.* / numeric / ≥ 1 · 4 ticket_available / int / required if limited · 4 max_buy_ticket / int / required if limited · 5 admission_pass_type / enum / §6.4.2 · 5 reentry_policy / enum / none/limited/unlimited · 5 max_reentries / int / 1–1000 if limited · 5 max_replacements / int / 1–100 · 5 replacement_fee / numeric / 0–100000 · per language {code}_title / string / required. [PROPOSED] event_id required, exists, owned; ticket_available ≥ 1; max per order ≤ availability. |
| Errors | "The Ticket name field is required for {language}"; "The variation name field is required."; "The variation price field is required." [VERIFIED]. |
| Post-conditions | `tickets`, `ticket_contents`, `variation_contents` rows. |
| Notifications | None. |
| Data entities | tickets, ticket_contents, variation_contents |
| Backend authority | `Organizer\TicketController@store`, `TicketRequest` (web); `Api\Organizer\TicketController@store` (app, no admission fields) |
| Previous / Next | UC-023 / UC-034, UC-036, UC-038, UC-026 |
| Web/Mobile | App creates tickets without admission settings (defaults to mobile QR) [VERIFIED]. |
| Audit | [PROPOSED] ticket_created with actor. Request payload is written to the application log (`Log::info($request->all())`) [VERIFIED] — remove (may contain operational data). |
| Security | LC-020 (no ownership check on web create/update/delete). |
| Edge cases | Random `rand(0, 999999)` slot ids can collide across tickets [VERIFIED] → LC-052. |
| Legacy conflicts | LC-020, LC-047, LC-052 |

#### UC-032 Edit ticket

| Field | Specification |
|---|---|
| Objective | Change ticket presentation, price, availability or admission settings safely. |
| Roles | Organizer; staff `tickets.manage`; Admin |
| Channel | Organizer Web (ORG-TKT-003); Admin; Organizer App |
| Preconditions | Ticket's event owned by actor [CANONICAL; not enforced on web — LC-020]. |
| Success scenario | Same form as UC-031 prefilled; on submit the ticket row and contents are updated; variations rebuilt from the submitted rows; removed seat-map slots cleaned up. |
| Business rules | BR-TKT-06 [CANONICAL] Price changes never alter existing bookings or payment orders (orders are snapshotted [VERIFIED `pricing_snapshot`]). BR-TKT-07 [PROPOSED] After the first sale: pricing type and variation names are locked (names are identity, BR-TKT-03); capacity cannot go below sold. BR-TKT-08 [PROPOSED] Changing admission pass type after issuance requires confirmation and does not invalidate existing credential assignments. |
| Validations | As UC-031. Availability is written as the new **remaining** stock [VERIFIED] — organizer must know how many are already sold (DEC-08). |
| Errors | As UC-031. |
| Post-conditions | Ticket updated. |
| Edge cases | Renaming a variation while a Razorpay order is pending makes finalization fail with "Ticket variation no longer exists." after payment [VERIFIED] (LC-017 recovery). |
| Legacy conflicts | LC-020, LC-051 |

#### UC-033 Delete / retire ticket

| Field | Specification |
|---|---|
| Objective | Remove a ticket from sale without losing history. |
| Roles | Organizer; staff `tickets.manage`; Admin |
| Current behavior [VERIFIED] | `destroy` and `bulk_delete` hard-delete by id with no ownership or sales check; database cascades delete pass products built on the ticket, and those cascade-delete pass entitlements of already-issued tickets. |
| Canonical behavior [PROPOSED] | 1. Unsold ticket: delete allowed. 2. Sold ticket: **retire** (status archived, hidden from sale); bookings, issued tickets, passes and entitlements remain. 3. A ticket used by a pass product cannot be deleted while the pass exists. |
| Validations | Owner; dependency checks above. |
| Errors | [PROPOSED] "This ticket has sales and can only be retired." / "Remove or retarget passes that use this ticket first." |
| Audit | [PROPOSED] ticket_retired / ticket_deleted with actor. |
| Legacy conflicts | LC-020, LC-047 |

#### UC-034 Configure ticket admission settings

| Field | Specification |
|---|---|
| Objective | Decide how holders of this ticket enter: mobile QR or a physical credential, collection, exit scanning, re-entry and replacement. |
| Roles | Organizer; staff `tickets.manage`; Admin |
| Channel | Ticket create/edit form (web only) |
| Inputs | admission_pass_type; collection_required; allow_mobile_qr_before_assignment; exit_scan_required; reentry_policy; max_reentries; replacement_allowed; max_replacements; replacement_fee (rupees → paise) |
| Success scenario | 1. User selects pass type. 2. If mobile QR: collection and replacement are disabled; QR before assignment forced true. 3. If physical: user chooses whether collection is required, whether the ticket QR admits before a credential is assigned, replacement rules and fee. 4. User sets exit scanning and re-entry. 5. Saved with the ticket. |
| Business rules | Engine behavior [VERIFIED]: physical type + no active credential → ticket QR admits only if QR-before-assignment is allowed and no credential is active; once a credential is active the ticket QR is refused ("Physical credential required"). Re-entry: `none` denies any second entry; `limited` allows `max_reentries` re-entries after the first entry; `unlimited` no cap. Exit scans always allowed when access is enabled (all tickets, see §6.4.3). [VERIFIED gap] `exit_scan_required` is stored but not enforced (re-entry does not require a prior exit beyond presence state, which already requires exit). `collection_required` is stored but not enforced at admission (LC-055). Replacement rules enforced by the access console controller (UC-105). |
| Validations | §UC-031 admission rows. |
| Post-conditions | Ticket admission fields saved. |
| Dependencies | Credential inventory types (LC-053), access policy (LC-049). |
| Legacy conflicts | LC-013, LC-049, LC-053, LC-055 |

#### UC-035 Configure early-bird discount

| Field | Specification |
|---|---|
| Objective | Offer a reduced price until a deadline. |
| Roles | Organizer; staff `tickets.manage`; Admin |
| Inputs | early_bird_discount_type enable/disable; discount_type fixed/percentage; amount; date; time |
| Success scenario | Stored on the ticket; the authoritative quote applies it while `now() ≤ date time` [VERIFIED]. |
| Business rules [VERIFIED] | Percentage 1–99 applied to the line total. Fixed amount between 1 and price − 1 (for variations: lowest variation price − 1), applied per ticket × quantity; line discount capped at line total. Applies to all variations of the ticket. Not applied to passes. Deadline interpreted in server time without event timezone (DEC-18). |
| Edge cases | A payment order created before the deadline but verified after keeps the snapshotted discount [VERIFIED snapshot]. |
| Legacy conflicts | DEC-18 |

#### UC-036 Configure seat map

| Field | Specification |
|---|---|
| Objective | Sell specific seats/areas for a ticket or variation with seat-level prices. |
| Roles | Organizer; staff `tickets.manage` (route names contain `seat_mapping` → tickets.manage [VERIFIED]); Admin |
| Channel | Organizer Web (ORG-TKT-004); Admin; Organizer App (`/api/organizer/seat-mapping/*`) |
| Success scenario | 1. Enable slot mode on a ticket/variation (`toggle-option-slot`). 2. Upload background map image. 3. Add slots (areas/rows) positioned by drag-and-drop. 4. Add seats per slot with name, type, price, deactivation. 5. Minimum seat price becomes the ticket's displayed "from" price [VERIFIED `slot_seat_min_price`, `EventStartingPriceService`]. 6. Customers select seats on PUB-EVT-003. |
| Business rules | [CANONICAL] Seat price and availability are server-authoritative and a sold seat can never be sold again. [VERIFIED] Booked seats are derived from `bookings.variation[*].seat_id`; the Payments V2 quote ignores seats (prices by ticket/variation) and finalization stores no `seat_id`, so seats bought through Razorpay V2 are not marked booked (LC-048). POS has no seat selection. |
| Validations | [PROPOSED] Seat names unique per slot; price ≥ 0; at least one active seat. |
| Security | SlotSeatController endpoints contain no ownership checks [VERIFIED] (LC-056). |
| Edge cases | Duplicated events strip slot ids and disable slot mode (UC-025) [VERIFIED]. |
| Legacy conflicts | LC-048, LC-052, LC-056, DEC-27 |

#### UC-037 Online event ticket

| Field | Specification |
|---|---|
| Objective | Sell access to an online event with one ticket managed from the event form. |
| Roles | Organizer; Admin |
| Success scenario | Price, pricing type, availability, max per order and early bird are entered in the event wizard (UC-020) and upserted into the event's single ticket by `syncOnlineTicket` [VERIFIED]. |
| Business rules | [PROPOSED] Exactly one online ticket; no seat maps, passes or physical credentials; ticket list shows it read-only with a link to the event wizard. Admission config irrelevant; the join link is the entitlement (UC-020). |
| Legacy conflicts | LC-026 |

#### UC-038 Create pass product

| Field | Specification |
|---|---|
| Objective | Sell multi-date access (day pass, weekend, season, combo, group) with explicit date entitlements. |
| Roles | Organizer; staff `tickets.manage`; Admin [UNKNOWN — no admin pass screen found] |
| Channel | Organizer Web (ORG-PASS-001) |
| Preconditions | Event owned [VERIFIED]; event has `event_dates` (multiple-date) [PROPOSED requirement]; a base ticket exists. |
| Inputs | ticket_id (base ticket of this event), code (unique per event), name, pass_type, selection_mode, choose_count, price (₹ → paise), inventory_type/quantity, max_per_order (1–50), admissions_per_holder (1–100), sales_channels (web, mobile, pos, box_office), credential_types, event_date_ids (≥ 1, of this event), active, sort_order |
| Success scenario | 1. User opens Passes for an event. 2. Fills the form and selects eligible dates. 3. Server validates. 4. Pass created with uuid and organizer; dates synced to `event_pass_dates`. 5. Pass appears in checkout and POS according to channels. |
| Business rules | [VERIFIED] pass_type values: single_day, any_one_day, multi_day, weekend, combo, season, group, couple, custom (labels only; no behavior differs by type). Selection: `fixed_dates` and `all_dates` both grant every date linked to the pass; `choose_n` requires the buyer to pick exactly `choose_count` dates from the linked dates. [PROPOSED] `all_dates` should mean all current and future event dates; `fixed_dates` the linked subset (DEC-28). `admissions_per_holder` and `credential_types` are stored but not used (LC-054). POS quotes passes on channel `box_office`, web on `web`, app on `mobile` [VERIFIED]; organizers must tick `box_office` for POS sale — `pos` is unused for passes (LC-016). |
| Validations | As listed in Inputs [VERIFIED `EventPassController::validated`]. |
| Errors | Laravel validation messages; "Event passes are not enabled." if tables missing [VERIFIED]. |
| Post-conditions | `event_pass_products`, `event_pass_dates`. |
| Downstream [VERIFIED] | Purchase → quote via `EventPassService::quote` → finalization/POS consumes pass stock and base ticket stock → issued ticket with `pass_product_id` → one `pass_entitlements` row per granted date → admission requires an active entitlement for today's date (UC-121). |
| Legacy conflicts | LC-016, LC-047, LC-054, DEC-25, DEC-28 |

#### UC-039 Edit / deactivate / delete pass

| Field | Specification |
|---|---|
| Objective | Maintain pass products without breaking sold entitlements. |
| Success scenario | Edit updates fields and re-syncs dates [VERIFIED]. Delete allowed only when `sold_quantity = 0`, else 409 "A sold pass cannot be deleted. Disable it instead." [VERIFIED]. Disable via `active = false`. |
| Business rules | [PROPOSED] Removing a date from a sold pass does not remove entitlements already issued; it only affects new sales. Price changes do not affect sold passes (snapshot) [VERIFIED]. |
| Edge cases | Deleting the base ticket (UC-033) or a linked event date (UC-019) cascades and deletes pass data including entitlements [VERIFIED FK cascades] (LC-047, LC-027). |
| Legacy conflicts | LC-027, LC-047 |

#### UC-040 Choose pass dates at purchase

| Field | Specification |
|---|---|
| Objective | Buyer obtains a pass and, where required, chooses the dates it covers. |
| Roles | Guest/Customer (web, app); POS operator |
| Success scenario | 1. Buyer selects a pass and quantity (≤ max per order). 2. If `choose_n`, buyer selects exactly N eligible dates. 3. Server quote validates channel, quantity, stock, dates [VERIFIED]. 4. Same dates apply to every pass in that line. 5. After payment/sale, each issued ticket receives entitlements for the selected dates. |
| Errors [VERIFIED] | "This pass is not sold through this channel." · "Invalid pass quantity." · "Requested pass quantity is no longer available." · "Select exactly N event dates." · "One or more selected dates are not valid for this pass." · "Select at least one event date." · "Pass stock changed before completion." |
| Web/Mobile | Customer app has no pass support (it uses legacy checkout, LC-005). |
| Legacy conflicts | LC-005 |

#### UC-041 Coupons

| Field | Specification |
|---|---|
| Objective | Apply a promotional code discount. |
| Roles | Admin creates (Event Bookings → Coupons) [VERIFIED]; organizers cannot create coupons [VERIFIED: no organizer route]; customer applies. |
| Current behavior [VERIFIED] | Coupon: name, code, type (fixed/percentage), value, events (JSON ids, empty = all), start/end date. Applied on the web via session (`apply-coupon`) and in legacy API checkout. **The Payments V2 quote ignores coupons**, so on the Razorpay path the charged amount excludes the coupon discount (LC-057). POS has no coupons. |
| Canonical [PROPOSED, DEC-06] | Discount rules evaluated inside the authoritative quote on all channels; usage limits per code and per customer; organizer-funded vs platform-funded flag; snapshot on the payment order. |
| Legacy conflicts | LC-057 |
| LC-058 | P0 | LD | Booking / Tickets | `FrontEnd\Event\BookingController@complete` (`/event-booking-complete?id=&booking_id=`) | When no customer is logged in, any booking (sequential id) is displayed and its issued tickets with secure QR tokens are created and shown — anyone can obtain valid entry QR codes for other buyers' bookings | Confirmation reachable only via signed, expiring token or by the owner | Signed URL for the completing session; guest retrieval by signed email link (DEC-31) | Unauthenticated request with another booking id → 403/404, no issuance |
| LC-059 | P1 | LD | Delivery | `TicketDeliveryService` writes `public/assets/admin/file/invoices/{uniqid}.pdf` and `public/assets/admin/qrcodes/secure_{uuid}.svg` | Ticket PDFs containing QR tokens are public files named by a time-based `uniqid()`, enumerable within a time window | Private storage, authorized download | Move to private disk + signed route; random public reference (DEC-34) | Direct URL to a ticket PDF without authorization fails |
| LC-061 | P1 | MA | Payments / Booking | `AuthoritativeTicketPricingService::quote` | No check that the event is published, not ended, or that the selected date is valid/future; `/api/v1/payments/*` is public | Quote rejects unsellable events, past sessions and invalid dates | Add sellability rules (UC-026, DEC-26) | Quote for unpublished or past event → 422 |
| LC-062 | P1 | MA | Payments | `PaymentController@verifyRazorpay` (rollback on finalize failure), `RazorpayWebhookController` (`captured_unfinalized` only), `PaymentReconciliationService` (no finalize) | Captured payments can end without booking; no automatic finalization, refund or operator queue | Captured-unfinalized orders are finalized from webhook/job or auto-refunded; visible in admin queue | Recovery job + admin "unfinalized payments" view | Kill browser after payment → booking created by webhook/job; stock failure → automatic refund |
| LC-063 | P2 | LD | Payments | `RazorpayController@notify` reads order uuid from session | Lost/expired session after payment → "Payment verification failed" although money captured | Order identified by gateway order id / signed callback parameter | Pass order uuid in callback | Notify succeeds without the original session |
| LC-064 | P2 | AC | Delivery | `BookingFinalizationService` calls `TicketDeliveryService::deliver` inside the finalization DB transaction | PDF rendering and SMTP inside locked transaction; failures swallowed; no delivery status/retry | Queue after commit; delivery log; resend (UC-099) | Dispatch job after commit | SMTP outage does not delay or roll back finalization; status visible |
| LC-065 | P2 | AC | Settlement | Web `notify` transfers inline; API verify dispatches `TransferOrganizerPayment` job | Two transfer behaviors for the same order type | Single post-commit transfer job | Use job in both | — |
| LC-066 | P2 | LD | Booking / API | `/api/v1/payments/razorpay/order` accepts `customer.customer_id` | Bookings can be attributed to any customer account | Customer identity from authenticated token only; else guest | Ignore body customer_id | — |
| LC-067 | P2 | MA | Customer app | `customer-app` bookings feature | No in-app ticket/QR display; customers depend on email PDF | App shows issued tickets from `/api/customers/booking/details` | App feature | — |
| LC-069 | P4 | LD | Booking | `BookingController@index` free branch `'zip_code' => $request->city` | Zip code stored as city | Correct mapping | Fix | — |
| LC-070 | P2 | MA | Tickets | `issued_tickets.status` | Tickets stay `active` when booking becomes rejected/voided/refunded; admission relies on booking status, customer views still show QR | Issued ticket status synchronized with booking/payment state machine | Status sync in booking/payment transitions | Refunded ticket shows cancelled; denied at gate with "ticket_refunded" |
| LC-071 | P3 | MA | Booking | `bookings.event_date` text; complete page matches `event_dates.start_date_time` by string | Fragile session reference | `event_date_id` FK | Add column, backfill | — |
| LC-072 | P2 | LG | Booking | `BookingController@index` falls back to `OfflineController` for any unknown gateway value; `OfflineGateway::find($request->gateway)` | Unexpected gateway values create pending offline bookings or errors | Explicit allowed methods only | Whitelist | Unknown gateway → validation error |
| LC-073 | P1 | LD | POS / Inventory | `BoxOfficeController@approveVoid` → `LockedTicketInventoryService::restore($eventId, $sale->pricing_snapshot)` | Snapshot is `['items'=>…]`, not an item list, so restore finds no ticket and silently restores nothing while the UI says "Sale voided and inventory restored."; pass stock never restored | Void restores ticket and pass inventory | Pass `pricing_snapshot['items']`; add pass restore | Void returns stock for ticket, variation and pass |
| LC-074 | P1 | MA | RBAC / Team | `Organizer\StaffController::event()` requires `box_office_enabled = 1`; assignments replaced by a single row | Ticket checkers and credential issuers cannot be created for venue events without box office; no staff member can work two events | Assignments for any eligible event, many per staff member | Remove box-office filter for non-sales roles; multi-assignment UI | Ticket checker assigned to a venue event can scan it |
| LC-075 | P1 | MA | RBAC | `Organizer\StaffController@store/update` | No limit on grantable permissions; with LC-003, staff with `team.manage` can create accounts with any permission (privilege escalation) | Grant only permissions the actor holds; `team.manage` organizer-only | Server-side grant check | Manager cannot grant `access.override` without holding it |
| LC-076 | P2 | AC | POS | `BoxOfficeController@store` (email required) vs `StaffBoxOfficeController@store` (email optional); settings `require_customer_*`, `allow_aadhaar`, `allow_customer_photo`, `auto_email_ticket` unused | Different customer rules per POS; settings ignored | One validation rule set driven by POS settings | Shared request class | Same input → same result on organizer and staff POS |
| LC-077 | P2 | AC | POS | `staff/pos.blade.php`, `StaffBoxOfficeController@index` (no passProducts), no staff hold/void routes | Staff POS lacks passes, holds, void request, workspace layout | One POS workspace filtered by permission | Converge views and routes | Staff can sell passes and hold orders when permitted |
| LC-078 | P2 | MA | POS | `BoxOfficeSaleService::sell` | No event state, session validity or date check; `event_date` free text | Sellability rules shared with online (LC-061); session id required for multi-date | Shared sellability service | Sale for ended/unpublished event or foreign date rejected |
| LC-079 | P2 | MA | POS / Cash | `BoxOfficeShiftService`, `approveVoid` | Organizer sales have no shift; voids after shift close don't adjust cash; no variance threshold; supervisor verification recorded as organizer | Every cash sale belongs to a shift; adjustments; variance approval | DEC-41 | Cash variance reconciles with voids |
| LC-080 | P3 | LD | POS UX | `organizer/box-office/pos.blade.php` media queries | Three-column grid kept down to 520 px (42 px rail, 190 px cart); unusable on phones | §6.7.2 mobile layout | Redesign | Phone POS completes a sale in single-column flow |
| LC-081 | P2 | AC | RBAC | `Organizer\StaffController::profile()` stores `permissions` = role defaults when none submitted | Role default changes never propagate; custom vs default indistinguishable | Role mode vs custom mode | `permission_mode` column | Updating a role default changes effective permissions of role-mode staff |
| LC-082 | P3 | MA | Admin | `OrganizerWorkforceController@impersonate` | No end-impersonation action or banner found; actions during impersonation attributed to staff | Bounded, visible, audited impersonation | Add end route/banner; mark actions | Actions during impersonation show admin id |

#### UC-042 Inventory authority & availability display

| Field | Specification |
|---|---|
| Objective | Show accurate availability and prevent overselling across web, app and POS. |
| Current behavior [VERIFIED] | Display: event "from" price via `EventStartingPriceService` (min of ticket, variation, seat-map minimum and pass prices). Checkout pre-check: quote checks remaining stock. Commit: web/app at finalization after payment; POS at sale under row lock; passes under row lock. No reservation between quote and payment (LC-017). Stock shared across dates (DEC-24). Refunds/cancellations do not restore stock except POS void. |
| Canonical [CANONICAL/PROPOSED] | One inventory service used by every channel: reserve (time-boxed) → commit on payment/sale → release on expiry/failure → restore on void/refund per policy. Availability = capacity − sold − active reservations, per product and (if date-bound) per date. |
| Acceptance | Last-ticket concurrency between web and POS never produces two sales; refunded tickets return to stock if DEC-29 says so. |
| Legacy conflicts | LC-017, LC-050, DEC-08, DEC-24, DEC-29 |

### 6.5 Booking & Checkout (UC-043 – UC-055)

#### 6.5.1 Channel map [VERIFIED]

| Channel | Selection & pricing | Payment | Finalization | Tickets shown | Authority status |
|---|---|---|---|---|---|
| Web — paid, Razorpay | `checkout2` builds session cart → `ticket.booking` → `RazorpayController@bookingProcess` re-quotes with `AuthoritativeTicketPricingService` | Razorpay Checkout; `notify` verifies signature | `BookingFinalizationService` in transaction; ledger; inline Route transfer | Booking complete page; email PDF; customer dashboard | Payments V2 (canonical), gaps LC-048, LC-057, LC-061 – LC-065 |
| Web — free | `checkout2` session totals (online events use client `pricing_type`) | none | `BookingController@index` free branch → `storeData` (event from client JSON) | as above | Legacy, client-influenced (LC-046) |
| Web — offline gateway | session `grand_total` | Manual (bank transfer etc.), optional attachment | `OfflineController` creates `pending` booking; organizer/admin marks completed | after approval | Legacy (LC-030, LC-072) |
| Web — 15 other gateways | session totals | PayPal, Stripe, Paytm, … | Gateway-specific notify | — | Legacy (LC-039) |
| Customer app | client-built payload; client-side amount | Gateway SDKs in app (Razorpay without mandatory server order) | `POST /api/event-booking` with client `total`, `paymentStatus` | **No in-app ticket/QR display** (LC-067) | Legacy, client-trusted (LC-005) |
| API v1 (no app uses it yet) | `POST /api/v1/payments/razorpay/order` server quote | Razorpay | `POST /api/v1/payments/razorpay/verify` | `GET /api/customers/booking/details` returns issued tickets | Payments V2 (canonical) |
| POS | `BoxOfficeSaleService` | Operator-attested cash/UPI/card/other | Same service | Print / email | Iteration 4 |

**Canonical [CANONICAL]:** every channel → authoritative quote → payment order (paid) or free confirmation decided by the server → booking finalization → ticket issuance → delivery. The client never decides price, free status or payment success.

#### 6.5.2 Booking record [VERIFIED `bookings`]
Order of record for every channel. Key fields: `booking_id` (public reference, `uniqid()`), `customer_id` (numeric id or the string `guest`), `organizer_id`, `event_id`, `ticket_id`, `variation` (JSON lines: ticket_id, name, qty, price, seat data, pass_product_id, event_date_ids, unique_id), `quantity`, `price` (float rupees), `tax`, `commission`, `discount`, `early_bird_discount`, customer contact and address fields, `paymentMethod`, `gatewayType` (online/offline), `paymentStatus` (pending / completed / free / rejected), `event_date` (free text), `invoice`, `attachment`, `scanned_tickets` (legacy), Box Office fields (`sales_channel`, `box_office_*`, identity fields).

---

#### UC-043 Browse and search events

| Field | Specification |
|---|---|
| Objective | Visitor finds events by text, category, location, date and type. |
| Roles | Guest, Customer |
| Channel | Public Website (PUB-HOME-001, PUB-EVT-001); Customer App (home, categories, search) |
| Success scenario | 1. Visitor opens Home or Events. 2. System lists active events with thumbnail, title, date, location/online label and "from" price (`EventStartingPriceService`). 3. Visitor filters (category, country/state/city, date, search text). 4. Visitor opens an event (UC-044). |
| Business rules | [CANONICAL] Only Published events (DEC-10; today `status = 1`) are listed; ended events either hidden or shown as past (DEC-37). [VERIFIED] "From" price is the minimum of ticket, variation, seat-map minimum and pass prices; free if minimum is 0. |
| Mobile | App home is driven by Mobile Homepage Studio campaigns (UC-148+). Mobile web: filters in a bottom sheet, single-column cards. |
| Legacy conflicts | DEC-37 |

#### UC-044 View event details and availability

| Field | Specification |
|---|---|
| Objective | Present everything needed to decide and select tickets. |
| Roles | Guest, Customer |
| Channel | PUB-EVT-002; Customer App event details; API `/api/event-details` |
| Success scenario | 1. System loads event content in the visitor's language, gallery, organizer, schedule (single or session list), location map or "Online event", refund policy, ticket list with prices, early-bird prices, availability state and passes. 2. Visitor chooses session (multi-date), tickets/variations/quantities or a pass (UC-045), or opens the seat map (UC-046). |
| Business rules | [CANONICAL] Meeting URL never shown publicly. [PROPOSED] Unpublished events return 404 to the public; organizer preview is a separate authenticated view. [PROPOSED] Sold-out shown per product (and per date when DEC-24 adopted). |
| Legacy conflicts | LC-061 |

#### UC-045 Select tickets, variations, date or pass

| Field | Specification |
|---|---|
| Objective | Build a cart for one event. |
| Roles | Guest (if guest checkout enabled), Customer |
| Channel | PUB-EVT-002 → `POST /check-out2`; App → local cart |
| Preconditions | Guest checkout setting `event_guest_checkout_status = 1`, or customer logged in (otherwise redirect to login with return path) [VERIFIED]. |
| Success scenario [VERIFIED] | Venue/box office: for each ticket/variation the visitor sets quantities; server reads prices from `tickets`, applies early bird, checks stock (`StockCheck`) and, for logged-in customers when guest checkout is off, the per-customer purchase limit (`isTicketPurchaseOnline`); stores the cart (`selTickets`), totals, event and chosen `event_date` in the session; redirects to checkout (UC-047). Online: price type is taken from the request (`pricing_type`) (LC-046). Pass: `EventPassService::quote` validates and stores a single pass line. |
| Business rules | [CANONICAL] Cart lines identify ticket id, variation id (DEC-23), pass id, date id(s), seats; quantities are requests; the server quote is the only price. [PROPOSED] For multi-date events a session must be chosen (date id, not text). Session cart is advisory; every payment step re-quotes [VERIFIED for Razorpay V2]. |
| Validations | quantity ≥ 1 per selected line [PROPOSED]; at least one line [VERIFIED V2 "Please select at least one ticket."]; date belongs to event [PROPOSED]. |
| Errors | "You can't purchase more tickets." (limit or seat conflict) [VERIFIED]; "Please select at least one pass." [VERIFIED]. |
| Next | UC-046 (seats) / UC-047 |
| Legacy conflicts | LC-046, LC-050, DEC-33 |

#### UC-046 Select seats

| Field | Specification |
|---|---|
| Objective | Choose specific seats for seat-mapped tickets. |
| Channel | PUB-EVT-003 (`/event/slot-mapping-seat`); not available in app or POS |
| Success scenario | 1. Seat map loads slots, seats, prices, booked and deactivated seats (booked derived from bookings JSON). 2. Visitor selects seats. 3. Selection is posted as `seatData` and grouped by slot into the cart. 4. On booking submit the system rechecks booked/deactivated seats [VERIFIED `slotBookedDeactiveCheck`]. |
| Business rules | [CANONICAL] Seats are priced and held by the server. [VERIFIED gap] Payments V2 ignores seats (LC-048). No hold during payment (DEC-20). |
| Legacy conflicts | LC-048, DEC-27 |

#### UC-047 Checkout: customer details

| Field | Specification |
|---|---|
| Objective | Capture buyer details and payment method. |
| Roles | Guest, Customer |
| Channel | PUB-CHK-001 (`/checkout`); App checkout screen |
| Success scenario | 1. Checkout shows cart summary, totals, tax, available online gateways and offline gateways. 2. Buyer enters first name, last name, email, phone, country, state, city, zip, address (prefilled for customers). 3. Buyer applies coupon (UC-041) optionally. 4. Buyer selects a gateway. 5. Submit → `POST /ticket-booking/{id}` (UC-048 free, UC-049 Razorpay, UC-051 offline). |
| Validations [VERIFIED Razorpay V2] | fname, lname, email (format), phone, country, address, gateway required. |
| Business rules | [PROPOSED] Only Razorpay and approved offline methods are offered (DEC-01). [PROPOSED] Address fields optional for ticket sales unless tax rules require them (DEC-35). [PROPOSED, DEC-36] Attendee names per ticket when the event requires named tickets. |
| Mobile | One column; cart summary collapsible at top; sticky "Pay ₹X" button reflecting the server quote. |
| Legacy conflicts | LC-057, DEC-35, DEC-36 |

#### UC-048 Free booking

| Field | Specification |
|---|---|
| Objective | Confirm a booking whose server-computed total is zero. |
| Roles | Guest, Customer |
| Channel | Web; App (via legacy API today) |
| Current behavior [VERIFIED] | If request `total` = 0 and session `sub_total` = 0 → free branch: event id taken from the posted `event` JSON, quantity from the request, lines from the session; `storeData` creates booking `paymentStatus = free`, decrements limited free-ticket stock; `TicketDeliveryService::deliver` issues tickets and emails them; redirect to complete page. For online events the session total comes from the client-posted `pricing_type` (LC-046). |
| Canonical behavior [CANONICAL] | 1. Server quotes cart. 2. If and only if quote total = 0 (all lines free or 100% discounted), server creates the booking as `free` in a transaction with inventory reservation/commit. 3. Issue tickets (UC-096), deliver (UC-098), show confirmation (UC-052). |
| Validations | Same as UC-047; quote total must be exactly 0. |
| Errors | Non-zero quote → route to payment; stock exhausted → "Tickets no longer available". |
| Post-conditions | Booking `free`; issued tickets; email sent. |
| Audit | Booking created with channel and actor (guest/customer id). |
| Security | Event, price type and quantities must never come from the client (LC-046). |
| Legacy conflicts | LC-046, LC-069 |

#### UC-049 Paid booking via Razorpay (web, Payments V2)

| Field | Specification |
|---|---|
| Objective | Take payment and confirm the booking exactly once. |
| Roles | Guest, Customer; Razorpay; System |
| Channel | Web |
| Preconditions | Cart in session; event sellable (LC-061). |
| Success scenario [VERIFIED] | 1. Validate buyer fields. 2. Map session cart to quote lines (ticket, quantity, variation, pass, dates). 3. `AuthoritativeTicketPricingService::quote` computes lines, early bird and subtotal; tax = platform tax rate (`basic_settings.tax`) on ticket amount. 4. `PaymentOrderService::createFromPricing` resolves fee rule (event type, channel `web`, method), platform fee, additional fees, settlement mode (managed vs Route eligibility), stores snapshot; idempotency key from session. 5. Customer snapshot saved. 6. `RazorpayRouteService::createOrder` creates the gateway order (with transfer instructions when split). 7. Razorpay Checkout opens with the server amount. 8. On success, `notify` reads the order uuid from session, verifies signature, locks the order, marks `paid`, finalizes the booking (stock commit, booking row, pass consumption, delivery), writes ledger, and for Route mode transfers to the organizer synchronously. 9. Redirect to complete page (UC-052). |
| Business rules | BR-BKG-01 Amount charged = order `customer_total` [VERIFIED]. BR-BKG-02 Finalization idempotent per order (`booking_id` set → return existing) [VERIFIED]. BR-BKG-03 [CANONICAL] Settlement eligibility never blocks payment; fallback to BookTKIT Managed. BR-BKG-04 [PROPOSED] Paid-but-unfinalizable orders go to `captured_unfinalized` and are automatically finalized or refunded (LC-062). |
| Validations | Signature verification [VERIFIED]; stock re-check under lock at finalization [VERIFIED]. |
| Errors [VERIFIED] | Verification or finalization failure → redirect to cancel page "Payment verification failed." (payment may already be captured — LC-062/063). "Ticket stock changed before payment completion." / "Ticket variation no longer exists." / "Pass stock changed before completion." |
| Post-conditions | payment_orders `paid`, bookings `completed`, ledger entries, issued tickets, email; transfer created (Route). |
| Notifications | Ticket email with PDF (UC-098). [PROPOSED] Organizer sale notification (DEC-38). |
| Dependencies | Payments domain (iteration 5). |
| Audit | payment_orders, payment_ledger_entries, payment_webhook_events [VERIFIED]. |
| Edge cases | Browser closes after payment: webhook records `captured_unfinalized`, nothing finalizes it (LC-062). Session lost: verification fails although paid (LC-063). Seat selection lost (LC-048). Coupon ignored (LC-057). |
| Legacy conflicts | LC-048, LC-057, LC-061, LC-062, LC-063, LC-064, LC-065 |

#### UC-050 Paid booking via customer app / API v1

| Field | Specification |
|---|---|
| Objective | Same outcome as UC-049 for mobile. |
| Current behavior [VERIFIED] | App computes amount locally, opens a gateway SDK (Razorpay, Stripe, PayPal, …; Razorpay order id optional), then posts the booking to public `POST /api/event-booking` with `total`, `tax`, `paymentStatus` and `gatewayType`; missing status with online gateway defaults to `completed`. |
| Canonical behavior [CANONICAL] | 1. App calls `POST /api/v1/payments/razorpay/order` with items and idempotency key (customer identity from the Sanctum token, not the body — LC-066). 2. Server quotes and creates the payment order + gateway order. 3. App opens Razorpay with server order id and amount. 4. App calls `POST /api/v1/payments/razorpay/verify`. 5. Server verifies, finalizes, issues and delivers; returns booking reference. 6. App opens booking details showing issued ticket QR codes (LC-067). Free carts use a server free-booking endpoint (UC-048). |
| Errors [VERIFIED v1] | 422 "Invalid payment signature." / "Payment verification failed."; validation errors on items/customer. |
| Migration | Ship app on v1; then reject client-supplied `paymentStatus` on the legacy endpoint; then retire it. |
| Legacy conflicts | LC-005, LC-066, LC-067 |

#### UC-051 Offline payment booking and approval

| Field | Specification |
|---|---|
| Objective | Allow bank-transfer-style payment confirmed manually. |
| Current behavior [VERIFIED] | Buyer picks an admin-configured offline gateway (optional proof attachment); booking created `pending` with session totals; no tickets until an organizer or admin sets payment status to `completed` (invoice generated, tickets issued lazily) or `rejected`. Any unrecognized gateway value also falls into this path. |
| Canonical [PROPOSED, DEC-01] | Offline payment is a payment order with method `offline`, status `awaiting_confirmation`; confirmation by an authorized role (organizer or finance) writes ledger, finalizes and issues tickets; rejection releases inventory; full audit (who, when, reference, attachment). |
| Legacy conflicts | LC-030, LC-072 |

#### UC-052 Booking confirmation page

| Field | Specification |
|---|---|
| Objective | Show the buyer that the booking succeeded and give access to tickets. |
| Channel | PUB-CHK-002 `GET /event-booking-complete?id={event}&booking_id={id}`; App success screen |
| Current behavior [VERIFIED] | Loads booking by numeric id and event id; if a customer is logged in and does not own it → 403; **if no one is logged in, any booking is shown, and its issued tickets (secure QR tokens) are created and displayed** (LC-058). |
| Canonical [CANONICAL] | Page is reachable only by the session that completed the purchase (signed, expiring confirmation token) or by the owning customer. Guests receive tickets by email and can retrieve them later via a signed link (DEC-31). |
| Content | Booking reference, event, date/session, quantity, amount paid, ticket QR codes (one per attendee), add-to-calendar, link to customer dashboard. Online: join instructions. |
| Mobile | QR codes full-width, one per card, swipeable; brightness hint. |
| Legacy conflicts | LC-058 |

#### UC-053 Customer views bookings and tickets

| Field | Specification |
|---|---|
| Objective | Customer finds past and upcoming bookings and presents tickets. |
| Channel | CUS-BKG-001, CUS-TKT-001; App bookings & booking details; API `/api/customers/bookings`, `/booking/details` |
| Success scenario [VERIFIED web/API] | List of own bookings; details verify ownership (web: silent redirect if not owner; API: scoped query) and call `ensureForBooking` to return issued tickets with tokens. |
| Business rules | [CANONICAL] Tickets displayed only for `completed`/`free` bookings [VERIFIED]. [PROPOSED] Voided, rejected or refunded bookings show tickets as cancelled, not as scannable QR (LC-070). [PROPOSED] Physical-credential tickets show collection instructions and credential status. |
| Mobile | App currently has no ticket QR rendering (LC-067). Canonical: wallet-style ticket cards, offline-available after first load. |
| Legacy conflicts | LC-067, LC-070 |

#### UC-054 Wishlist

| Field | Specification |
|---|---|
| Objective | Save events for later. |
| Channel | CUS-WSH-001; App wishlist; API `/api/customers/wishlists` |
| Success scenario | Add/remove event from wishlist; list wishlist [VERIFIED routes]. |
| Business rules | Only customers; deleting an event removes wishlist rows [VERIFIED]. |

#### UC-055 Customer cancellation / ticket transfer

| Field | Specification |
|---|---|
| Objective | Allow customers to cancel or transfer bookings when policy allows. |
| Current behavior [VERIFIED] | No customer cancellation or transfer feature exists; the event refund policy is display text only. |
| Canonical | [UNKNOWN — DEC-07, DEC-32] Define refund-request flow and whether tickets are transferable (named tickets, DEC-36). |

---

### 6.7 POS / Box Office (UC-071 – UC-088)

#### 6.7.1 POS model [VERIFIED unless marked]

| Concept | Implementation |
|---|---|
| Eligible events | Owned events with `event_type = box_office` or `box_office_enabled = 1`; at least one active `box_office_locations` row |
| Workspaces | Organizer POS `/organizer/box-office` (3-column workspace: event rail, product grid, cart; customer and payment modals; holds). Staff POS `/staff/box-office` (simple form; assigned events only; no passes, no holds) |
| Sale authority | `BoxOfficeSaleService::sell` — idempotent by client-generated `sale_uuid`, one DB transaction, row-locked inventory, pass stock consumption, fee rule (channel `pos`), booking (`booking_source = box_office`, `paymentStatus = completed`, reference `BO-xxxxxxxxxxxx`), `box_office_sales` row, issued tickets, sale log, POS ledger, optional email after commit |
| Payment methods | cash, upi, card, other — recorded by the operator; no gateway verification |
| Totals | Ticket amount from authoritative quote (channel `box_office`); platform fee from POS fee rule; **no tax, no additional fees** (LC-016) |
| Shifts | Required for staff sales (open shift for the same event + location); not used for organizer sales |
| Holds | Saved carts (organizer only) with customer name/phone/email and expiry (`hold_minutes`, default 10, max 120); no inventory reservation |
| Void | Request (reason) → approve; scanned tickets block void; sale `voided`, booking `rejected`, POS ledger reversed |
| Settings | Organizer-level `box_office_settings`: hold minutes, receipt width 58/80 mm, allow cash/UPI/card/other, require name/phone, allow Aadhaar, allow photo, auto-email |
| Identity capture | Name, phone, email, age; optional Aadhaar number (encrypted + last 4), Aadhaar document and customer photo on private storage |

**Canonical POS flow [CANONICAL]:** Select event → session/date → counter → products → cart → customer → server quote → Hold **or** Payment → authoritative sale → booking → issued tickets → print/email → optional credential assignment (UC-103) → next customer. POS uses the same pricing, fee, tax, payment-order and ledger engine as online (LC-016).

#### 6.7.2 Screen specification — ORG-POS-001 / STF-POS-001 POS workspace

| Attribute | Specification |
|---|---|
| Actors | Organizer; staff with `box_office.sell` (assigned event + location, open shift) |
| Purpose | Sell and issue tickets at a counter quickly without leaving the workspace |
| Data shown | Event rail (eligible events), session selector (multi-date), counter selector, product grid (tickets, variations, passes with price and live availability), cart lines, totals (subtotal, discount, fee, tax, total), held orders, current shift status |
| Primary CTA | "Take payment" |
| Secondary actions | Hold, Resume hold, Clear cart, Customer details, Reprint last, Open/close shift (staff), Void request |
| Desktop (≥ 1100 px) [VERIFIED structure] | Three columns: narrow event rail · product grid · cart panel; customer and payment in modals |
| Tablet (760–1100 px) | [VERIFIED] columns shrink (58 px rail, 280 px cart). [PROPOSED] Two columns: products + cart; event/counter in header dropdowns; payment as right-side drawer |
| Mobile (< 760 px) | [VERIFIED] three-column grid persists down to 520 px (42 px rail, 190 px cart) (LC-080). [PROPOSED] Single column: header with event/session/counter chips → product list with steppers → sticky bottom bar "n items · ₹total · Pay" → full-screen cart/payment sheet; hold/resume in overflow menu; camera available for credential assignment after sale |
| Loading | Skeleton product grid; disabled Pay while quote recalculates |
| Empty | No eligible events: "Enable Box Office on an event and add a counter" (organizer) / "You are not assigned to an active counter" (staff) |
| Validation | Inline per field in customer sheet; quantity limits on steppers |
| Error | Inventory changed → cart line highlighted with new availability; shift missing → blocking banner with "Open shift" |
| Success | Receipt/ticket print view with "New sale" as primary action; email status |
| Next | ORG-POS-002 print → back to ORG-POS-001 |
| Backend | BoxOfficeController / StaffBoxOfficeController, BoxOfficeSaleService, BoxOfficeOperationsController |
| Conflicts | LC-016, LC-031, LC-032, LC-077, LC-080 |

---

#### UC-071 Open POS workspace

| Field | Specification |
|---|---|
| Objective | Start selling at a counter. |
| Roles | Organizer; staff `box_office.sell` |
| Channel | POS (Organizer Web / Staff Web) |
| Preconditions | Eligible event exists; staff: active, password changed, assigned to event (+ location for sales roles), open shift (UC-083) before the first sale. |
| Success scenario | 1. User opens POS. 2. System lists eligible events (organizer: all owned eligible events; staff: assigned events) with locations, tickets, dates and (organizer only) passes. 3. User proceeds to UC-072. |
| Business rules | [CANONICAL] One POS workspace for organizer and staff with features filtered by permission (LC-077). [PROPOSED] Organizer selling also requires a shift (DEC-41). [PROPOSED] Events outside sellable state are not listed (LC-078). |
| Errors | Staff without assignment → 403 [VERIFIED middleware]. |
| Next | UC-072 |
| Legacy conflicts | LC-003, LC-077 |

#### UC-072 Select event, session and counter

| Field | Specification |
|---|---|
| Objective | Fix the sale context. |
| Success scenario | User selects event, session (multi-date events) and counter. |
| Business rules | [VERIFIED] Counter must be an active location of the event; staff must be assigned to that event+location. [VERIFIED gap] Session is posted as free-text `event_date`; not validated. [CANONICAL] Session is an `event_date_id` of the event and is required for multi-date events; passes carry their own dates (UC-040). |
| Errors | "Invalid box office location." [VERIFIED] |
| Legacy conflicts | LC-071, LC-078 |

#### UC-073 Build cart

| Field | Specification |
|---|---|
| Objective | Add tickets, variations and passes with quantities. |
| Success scenario | 1. User taps products and adjusts quantities. 2. Cart shows lines with server-derived unit prices. 3. Passes with `choose_n` open a date picker. |
| Business rules | [CANONICAL] Displayed prices are refreshed from the server quote before payment. Passes are sellable only when their channels include POS (`box_office` today, LC-016). Max per order enforced (LC-050). Seat-mapped tickets not sellable at POS (DEC-27). |
| Validations | quantity ≥ 1 [VERIFIED server]; availability [VERIFIED at sale]. |
| Legacy conflicts | LC-050, LC-077 |

#### UC-074 Capture customer details and identity

| Field | Specification |
|---|---|
| Objective | Identify the buyer for receipts, ticket delivery and holds. |
| Inputs [VERIFIED] | customer_name (required, ≤ 120), customer_phone (required, ≤ 30), customer_email (organizer POS: **required**; staff POS: optional), customer_age (1–120), aadhaar_number (12 digits), aadhaar_document (jpg/png/pdf ≤ 5 MB), customer_photo, deliver_email |
| Business rules | [CANONICAL — product rule] Email is mandatory for holds and whenever tickets are emailed; [PROPOSED] mandatory for every POS sale unless the organizer disables it in settings (single rule for organizer and staff, LC-076). Settings `require_customer_name/phone`, `allow_aadhaar`, `allow_customer_photo` must drive the form and server validation [VERIFIED: stored, not enforced]. Government ID capture governed by DEC-22. |
| Security | Aadhaar number encrypted (`Crypt`), only last 4 displayed; documents on private disk [VERIFIED]. [PROPOSED] Access to identity documents audited and restricted to organizer/supervisor. |
| Legacy conflicts | LC-042, LC-076 |

#### UC-075 Quote and totals

| Field | Specification |
|---|---|
| Objective | Show the exact amount to collect before payment. |
| Current [VERIFIED] | No separate quote endpoint; the client computes the display; the server computes at sale: ticket amount (quote), platform fee (POS fee rule, quantity ignored LC-015), customer total. Tax and additional fees are not applied. |
| Canonical [CANONICAL] | `POST quote` returns lines, discounts, fees, tax and total from the same engine as online; payment screen shows that total; sale rejects if the total changed since the quote (return new quote). |
| Legacy conflicts | LC-015, LC-016 |

#### UC-076 Take payment

| Field | Specification |
|---|---|
| Objective | Collect money by an allowed method. |
| Success scenario | 1. Operator opens payment. 2. Chooses cash, UPI, card or other. 3. Cash: enters cash received; system shows change [VERIFIED field `cash_received`]. 4. UPI/card: operator confirms receipt (and optionally enters reference). 5. Operator confirms → UC-077. |
| Business rules | [VERIFIED] Methods are not checked against POS settings (LC-031). [VERIFIED] No gateway confirmation; payment is operator-attested. [UNKNOWN DEC-40] Whether UPI/card must be verified (dynamic UPI QR / Razorpay POS) before issuance. [PROPOSED] Payment reference required for UPI/card; cash sales bound to the open shift. |
| Errors | Payment failure (customer cannot pay) → return to cart or Hold (UC-080); no sale created. |
| Legacy conflicts | LC-031, DEC-40 |

#### UC-077 Complete sale

| Field | Specification |
|---|---|
| Objective | Create the sale, booking and tickets exactly once. |
| Roles | Organizer; staff `box_office.sell` |
| Trigger | Payment confirmed (form submit with `sale_uuid`). |
| Success scenario [VERIFIED] | 1. Validate inputs. 2. Staff: assignment check; open shift for event+location (locked). 3. Idempotency: existing `sale_uuid` for this organizer returns the existing sale; for another organizer → error. 4. Event must be owned and POS-enabled; location active. 5. Quote (channel `box_office`). 6. Lock and decrement inventory; consume pass stock. 7. Resolve POS fee rule; compute fee. 8. Create booking (completed), sale (with shift, staff, method, totals, snapshot), issued tickets, sale log `sale_created`, POS ledger entries (counter receipt, organizer receivable, platform fee receivable). 9. Commit. 10. Email tickets if requested. 11. Redirect to print view (UC-078). |
| Business rules | BR-POS-01 Idempotent per `sale_uuid` [VERIFIED]. BR-POS-02 Inventory decrement under row lock [VERIFIED]. BR-POS-03 [CANONICAL] Payment order + unified ledger (LC-016). BR-POS-04 [PROPOSED] Sale is attributed to the acting staff member on every route (LC-003/LC-034). |
| Validations | As UC-072 – UC-076. |
| Errors [VERIFIED] | "Open a shift for this event and counter before selling." · "Invalid box office location." · "Sale identifier is not valid for this organizer." · "Ticket inventory changed. Please refresh." · "Ticket variation inventory changed. Please refresh." · "Invalid ticket variation." · "Quantity must be at least 1." · pass errors (UC-040). |
| Post-conditions | booking completed; box_office_sales completed; issued tickets active; ledger entries; shift totals include sale. |
| Notifications | Ticket email if requested/auto. |
| Audit | `box_office_sale_logs` (sale_created) [VERIFIED]; actor recorded as organizer when staff act through organizer routes (LC-003). |
| Edge cases | Double-click / retry: same `sale_uuid` returns same sale [VERIFIED]. Network failure after commit: client retries with same uuid → idempotent [VERIFIED]. Printer failure → reprint (UC-086). |
| Next | UC-078, UC-079, UC-103 |
| Legacy conflicts | LC-003, LC-015, LC-016, LC-078 |

#### UC-078 Print receipt and tickets

| Field | Specification |
|---|---|
| Success scenario [VERIFIED] | Print view loads the sale (organizer: any own sale; staff: only own sales) and issued tickets; renders receipt and one ticket per attendee with secure QR at configured receipt width. |
| Business rules | [PROPOSED] Print view shows credential collection instructions when the ticket requires a physical credential. |
| Next | New sale (UC-073) or credential assignment (UC-103) |

#### UC-079 Email tickets from POS

| Field | Specification |
|---|---|
| Success scenario | If `deliver_email` (or auto-email setting [PROPOSED; stored but unused]) and email present → `TicketDeliveryService::deliver` after commit [VERIFIED]. |
| Errors | Failure logged; sale unaffected [VERIFIED]. [PROPOSED] Show "Email failed — retry" in the print view (UC-099). |
| Legacy conflicts | LC-064 |

#### UC-080 Hold order

| Field | Specification |
|---|---|
| Objective | Park a cart for a customer who will pay shortly. |
| Roles | Organizer [VERIFIED]; staff [PROPOSED, LC-032] |
| Inputs [VERIFIED] | event_id, location_id, items (ticket_id, quantity, variation), customer name, phone, **email (required)** |
| Success scenario [VERIFIED] | Event/location validated; hold created `held` with expiry now + hold_minutes (1–120, default 10); JSON response; hold listed in POS (latest 50). |
| Business rules | [VERIFIED] Holds do not reserve inventory; passes cannot be held. [PROPOSED, DEC-21] Holds reserve inventory until expiry; passes supported; holds bound to counter and staff. |
| Next | UC-081 / UC-082 |
| Legacy conflicts | LC-032, DEC-21 |

#### UC-081 Resume hold

| Field | Specification |
|---|---|
| Success scenario [VERIFIED] | Expired holds are marked `expired` first; a `held` hold is set `resumed` and returned to the client, which reloads the cart. |
| Business rules | [PROPOSED] Resumed cart is re-quoted; the sale references the hold id; hold becomes `converted` on sale. Today there is no link between hold and sale. |

#### UC-082 Cancel or expire hold

| Field | Specification |
|---|---|
| Success scenario [VERIFIED] | Cancel sets `held`/`resumed` → `cancelled`. Expiry is applied lazily whenever holds are listed or resumed. |
| Business rules | [PROPOSED] Scheduled expiry releases reservations. |

#### UC-083 Open shift

| Field | Specification |
|---|---|
| Roles | Staff `box_office.sell` |
| Inputs | event_id, location_id (assigned), opening_cash (₹ → paise) |
| Success scenario [VERIFIED] | Assignment checked; refuses if the staff member already has an open shift ("Close the current shift before opening another."); creates shift `open`. |
| Legacy conflicts | DEC-41 |

#### UC-084 Close shift

| Field | Specification |
|---|---|
| Inputs | declared_closing_cash |
| Success scenario [VERIFIED] | Expected cash = opening cash + completed cash sales in the shift; variance = declared − expected; status `closed`. |
| Business rules | [PROPOSED] UPI/card totals shown for reconciliation; variance above threshold requires note and supervisor verification; voids after close create an adjustment on the shift (LC-079). |

#### UC-085 Verify shift

| Field | Specification |
|---|---|
| Roles | Organizer; supervisor `shifts.verify` (via organizer area today) |
| Success scenario [VERIFIED] | Only `closed` shifts can be verified; sets `verified`, verifier, time, note. |
| Business rules | [PROPOSED] Verifier cannot verify own shift; verification locks the shift's sales from void without supervisor override. |
| Legacy conflicts | LC-003, LC-079 |

#### UC-086 Reprint

| Field | Specification |
|---|---|
| Roles | Organizer; staff `box_office.reprint` (own sales only [VERIFIED]) |
| Success scenario [VERIFIED] | Logs `reprint` with actor and opens the print view. |
| Business rules | [PROPOSED] Reprint count shown on the print; reprint of a voided sale forbidden. |

#### UC-087 Void request and approval

| Field | Specification |
|---|---|
| Objective | Reverse a POS sale made in error. |
| Roles | Request: organizer [VERIFIED]; staff `box_office.void_request` [PROPOSED — no staff route]. Approve: organizer [VERIFIED]; staff `box_office.void_approve` [PROPOSED]. |
| Success scenario [VERIFIED] | 1. Request with reason (completed sale). 2. Approve: lock sale; if any issued ticket has been checked in → 422 "Scanned tickets cannot be voided."; inventory restore attempted; sale `voided`; booking `rejected`; POS ledger reversal; request `approved`; log `void_approved`. |
| Business rules | [VERIFIED defect] Inventory restore receives the pricing snapshot instead of the item list and restores nothing; pass stock not restored (LC-073). [VERIFIED gap] Issued tickets and assigned credentials remain active (LC-033). [PROPOSED] Approver ≠ requester for staff; cash refund recorded against the current shift; card/UPI refunds handled per DEC-42. |
| Post-conditions (canonical) | Sale voided; booking voided; issued tickets revoked; credentials revoked/unassigned; inventory and pass stock restored; ledger reversed; shift adjusted. |
| Legacy conflicts | LC-033, LC-073, LC-079, DEC-42 |

#### UC-088 POS settings and reports

| Field | Specification |
|---|---|
| Settings [VERIFIED] | Organizer-level settings row (event-level rows supported by schema, UI edits organizer level only); admin platform defaults (ADM-BO-001). Fields listed in §6.7.1. |
| Reports [VERIFIED] | Filters: event, location, staff, payment method, date range. Summary: sales count, gross, platform fee, organizer amount, voids; totals by method; shifts; online vs box office completed bookings; total admissions; reprints. |
| Business rules | [PROPOSED] Reports include tax, refunds, variance and per-staff performance; staff with `reports.view` see only assigned events. |
| Legacy conflicts | LC-031, LC-076 |

---

### 6.8 Organizer Team / RBAC (UC-089 – UC-095)

#### 6.8.1 Screen specification — ORG-TEAM-001 Team

| Attribute | Specification |
|---|---|
| Actor | Organizer (staff with `team.manage` via LC-003 today) |
| Data shown [VERIFIED] | Team members with role, department, active state, assignments (event, location), sales count; create/edit form with role, department, job title, permissions checklist, event, location, password, photo, notes |
| Primary CTA | "Add team member" |
| Secondary | Edit, reset password, archive |
| Desktop | Table + side drawer form |
| Tablet | Table (name, role, assignment, status) + full-height drawer |
| Mobile | Cards per member; form as full-screen sheet with sections (Profile · Role & permissions · Assignments · Security); permissions grouped by job (Sell, Admit, Credentials, Manage) with role defaults pre-ticked and "custom" badge when changed |
| Errors | "A Box Office location is required for this role." · "Invalid Box Office location." [VERIFIED] |

#### UC-089 Create team member

| Field | Specification |
|---|---|
| Objective | Give a person an operational login scoped to the organizer's events. |
| Roles | Organizer; staff `team.manage` [PROPOSED with grant limits] |
| Inputs [VERIFIED] | name, username (unique per organizer), email (unique per organizer, optional), phone, password (≥ 8), role, department, job title, notes, active, permissions (subset of vocabulary), event_id, box_office_location_id, photo |
| Success scenario [VERIFIED] | 1. Validate. 2. Event must be owned **and box-office-enabled**. 3. Sales roles (sales_agent, cashier, box_office_supervisor) require a location of that event. 4. Create staff with hashed password, `must_change_password = true`, permissions = submitted list or role defaults. 5. Create one assignment. 6. Audit `created`. |
| Business rules | [VERIFIED gap] Staff can be assigned only to box-office-enabled events and to one event (LC-074). [CANONICAL] Scanner and credential staff can be assigned to any venue/box office event; multiple assignments; optional gate scope (LC-010). [PROPOSED] A manager cannot grant permissions they do not hold; `team.manage` grantable only by the organizer (LC-075). |
| Post-conditions | organizer_staff, organizer_staff_assignments, staff_audit_logs. |
| Next | UC-007 staff login → forced password change |
| Legacy conflicts | LC-074, LC-075, LC-081 |

#### UC-090 Edit role and permissions

| Field | Specification |
|---|---|
| Success scenario [VERIFIED] | Update profile, role, permissions; assignments replaced by the submitted single assignment; deactivation revokes API tokens; audit `updated`. |
| Business rules | [VERIFIED] Permissions saved as an explicit list (role defaults copied), so later changes to role defaults never reach existing staff (LC-081). [PROPOSED] Store `permission_mode = role|custom`; effective permissions = role defaults when mode is role. Deactivation must also end web sessions [UNKNOWN — only tokens revoked]. |
| Legacy conflicts | LC-081 |

#### UC-091 Manage assignments

| Field | Specification |
|---|---|
| Current [VERIFIED] | Exactly one (event, optional location) per staff member, replaced on each edit. Enforced for staff POS, shifts and staff scanner API; not enforced in the organizer area (LC-003). |
| Canonical [PROPOSED] | Many assignments per staff member; each = event + optional location + optional gates/zones + optional date range; enforcement on every channel. |
| Legacy conflicts | LC-003, LC-010, LC-074 |

#### UC-092 Reset password and forced change

| Field | Specification |
|---|---|
| Success scenario [VERIFIED] | Organizer sets a temporary password (≥ 8); `must_change_password = true`; API tokens revoked; audit `password_reset`. On next login the staff member is redirected to change password; staff routes block other pages until changed. |
| Gap | Organizer-area routes reachable by staff (LC-003) do not enforce the forced change. |

#### UC-093 Archive team member

| Field | Specification |
|---|---|
| Success scenario [VERIFIED] | Audit `deleted` with name/username; tokens revoked; staff deactivated and anonymized (name suffixed "[Archived]", username replaced, email/phone/photo removed); historical sales retained. |
| Business rules | [PROPOSED] Open shift must be closed first; active web sessions ended. |

#### UC-094 Admin workforce oversight

| Field | Specification |
|---|---|
| Roles | Admin [VERIFIED, no permission middleware — LC-019] |
| Success scenario [VERIFIED] | List all staff across organizers with sales counts; edit, reset password, archive; all audited with `admin_id`. |

#### UC-095 Admin impersonates staff

| Field | Specification |
|---|---|
| Success scenario [VERIFIED] | Admin starts impersonation of an active staff member: audit `admin_impersonation_started`, session flag with admin id, staff guard login. |
| Business rules | [PROPOSED] Requires explicit admin permission (LC-019), reason, visible banner, explicit end action audited [UNKNOWN — no end route found], and blocks financial actions (sales, voids) during impersonation (DEC-15). |
| Legacy conflicts | LC-019, DEC-15 |

### 6.9 Ticket Issuance & Delivery (UC-096 – UC-100)

#### UC-096 Issue tickets

| Field | Specification |
|---|---|
| Objective | Create one attendee entitlement per admitted person for a confirmed booking. |
| Roles | System |
| Trigger [VERIFIED] | Any call to `TicketIssuanceService::ensureForBooking`: booking finalization delivery, free booking delivery, POS sale/print, booking complete page, customer web/API booking details, organizer/admin booking views, legacy scanner adapter. Issuance is **lazy and idempotent**. |
| Preconditions | Booking `paymentStatus` ∈ {completed, free} [VERIFIED]. |
| Success scenario [VERIFIED] | 1. Lock booking row. 2. If issued tickets exist, return them. 3. Expand booking lines: one issued ticket per unit (`qty`), name from line, ticket type id, pass product id; legacy bookings without lines get `quantity` generic tickets. 4. For each: UUID, token `btk_{uuid}.{HMAC-SHA256(uuid, APP_KEY)}`, store only `token_hash`, status `active`; legacy `scanned_tickets` entries are mapped to `checked_in_at` with type `legacy`. 5. For pass lines create one `pass_entitlements` row per selected date. 6. Return tickets with plain tokens for display/delivery. |
| Business rules | BR-ISS-01 `issued_tickets` is the entitlement authority [CANONICAL]. BR-ISS-02 Exactly one issuance per booking unit, concurrency-safe [VERIFIED lock]. BR-ISS-03 Token is reproducible from UUID and app key; rotating `APP_KEY` invalidates every printed/emailed QR [VERIFIED derivation] (DEC-39). BR-ISS-04 [PROPOSED] Issuance happens eagerly at finalization; view endpoints must only read. BR-ISS-05 [PROPOSED] Status sync: booking rejected/voided/refunded → issued tickets `revoked`/`refunded` (LC-070). |
| Data entities | issued_tickets, pass_entitlements, bookings |
| Audit | issued_at [VERIFIED]; [PROPOSED] issuance channel and actor. |
| Edge cases | Booking marked completed manually by organizer gets tickets on next view [VERIFIED]. Forged legacy API bookings receive real tickets (LC-005). Viewing a guest booking via the complete page issues its tickets (LC-058). |
| Legacy conflicts | LC-005, LC-058, LC-070 |

#### UC-097 Present secure ticket QR

| Field | Specification |
|---|---|
| Objective | Give the attendee a scannable credential that cannot be forged. |
| Success scenario | Token rendered as QR (web views, PDF, print, app). Scanner submits the token; the engine hashes and matches `token_hash` (UC-111). |
| Business rules | [VERIFIED] Legacy `booking_id__unique_id` QR values are refused by the canonical engine ("Legacy QR is no longer accepted") but still accepted by legacy endpoints (LC-001). [PROPOSED] QR images are not written to public storage (LC-059). |
| Legacy conflicts | LC-001, LC-059 |

#### UC-098 Deliver tickets by email

| Field | Specification |
|---|---|
| Objective | Send tickets to the buyer reliably. |
| Trigger [VERIFIED] | Finalization (web/API Razorpay), free booking, POS sale with "email" option, `BookingInvoiceJob`. |
| Success scenario [VERIFIED] | 1. Skip unless booking completed/free and email present. 2. Ensure issued tickets. 3. Write QR SVGs to `public/assets/admin/qrcodes/secure_{uuid}.svg`. 4. Render PDF (`frontend.event.invoice`) to `public/assets/admin/file/invoices/{booking_id}.pdf` and store the filename on the booking. 5. Build email from template `event_booking` with placeholders `{customer_name}`, `{order_id}`, `{website_title}`, `{title}`, `{meeting_url}` (online only) plus a confirmation block; attach PDF; send via SMTP settings. 6. Failures are logged and return false. |
| Business rules | [PROPOSED] Delivery runs after commit via queue with retry and a delivery status per booking (LC-064). [PROPOSED] Ticket PDFs are private files served through an authorized, signed route (LC-059). [CANONICAL] Online join link only to confirmed bookings. |
| Errors | SMTP failure → booking still confirmed; [PROPOSED] visible "Email not delivered — resend" state for organizer and customer. |
| Legacy conflicts | LC-059, LC-064 |

#### UC-099 Resend / reissue tickets

| Field | Specification |
|---|---|
| Current behavior [VERIFIED] | No resend action for organizers, admins or customers (only POS reprint, iteration 4). |
| Canonical [PROPOSED] | Organizer/admin/customer "Resend tickets" (rate-limited) re-sends existing issued tickets. "Reissue" (rotate token) revokes old tokens and issues new ones for a compromised ticket, audited, with reason. |

#### UC-100 Print tickets

| Field | Specification |
|---|---|
| Current behavior [VERIFIED] | POS and staff print views render receipts/tickets for a sale; customers can print the PDF. |
| Canonical | See UC-078 (print) and UC-086 (reprint). |


### 6.6, 6.10 – 6.12 Remaining domains
Pending iterations 5–9 (see §0.2). IDs are reserved in §6.1.

---

## 7. State Machines

### 7.1 Event

**Current [VERIFIED]:** `status` 0 (inactive) / 1 (active); `is_featured`; "past" derived from `end_date_time`. No draft, paused, cancelled or archived state. Any status value is accepted on create/edit.

**Canonical [PROPOSED — DEC-10]:**

| State | Meaning | Sellable | Public | Scannable |
|---|---|---|---|---|
| Draft | Being configured | No | No | No |
| Published | Live for sale | Yes | Yes | Yes (on event dates) |
| Paused | Sales stopped by organizer | No | Yes (sold out / unavailable) | Yes |
| Live (derived) | Published and within a session window | Yes | Yes | Yes |
| Ended (derived) | After last session end | No | Yes (past) | No (except configured grace) |
| Cancelled | Event will not happen | No | Yes (cancelled notice) | No |
| Archived | Hidden, read-only history | No | No | No |

| From | Event / action | To | Guard | Owner |
|---|---|---|---|---|
| ∅ | Create / duplicate | Draft | — | EventFormService |
| Draft | Publish | Published | Readiness checks UC-026 | Publish service [new] |
| Published | Pause | Paused | — | Publish service |
| Paused | Resume | Published | Readiness | Publish service |
| Published | Session window reached | Live | time | derived |
| Live / Published | Last session ends | Ended | time | derived |
| Draft | Delete | ∅ | no commercial records | EventFormService [new] |
| Published / Paused | Cancel | Cancelled | DEC-04 refund policy | Cancellation service [new] |
| Ended / Cancelled / Paused | Archive | Archived | — | EventFormService [new] |

Mapping for migration: status 1 → Published; status 0 with no sales → Draft; status 0 with sales → Paused.

### 7.2 Ticket type and pass product

**Current [VERIFIED]:** tickets have no status (existence = sellable; deletion = gone); pass products have `active` true/false.

**Canonical [PROPOSED]:**

| From | Action | To | Guard |
|---|---|---|---|
| ∅ | Create | Active | Event owned; valid pricing |
| Active | Hide | Hidden | — (not sellable, still valid for holders) |
| Hidden | Show | Active | — |
| Active | Capacity reached (derived) | Sold out | capacity − sold − reservations = 0 |
| Sold out | Capacity raised / stock released | Active | — |
| Active / Hidden / Sold out | Retire | Archived | Always allowed; history kept |
| Active / Hidden | Delete | ∅ | No sales, no dependent passes |

### 7.3 Booking

**Current [VERIFIED]:** `paymentStatus` ∈ {pending, completed, free, rejected}. Transitions: created as completed (Razorpay V2, legacy API default), free, or pending (offline); pending → completed/rejected by organizer/admin; completed → rejected by POS void. Payment order separately: created → paid; webhook may set `captured_unfinalized`; `refund_status` none/partial/full.

**Canonical [PROPOSED]:**

| From | Event | To | Guard / owner |
|---|---|---|---|
| ∅ | Server quote total > 0, payment order created | Pending payment | PaymentOrderService |
| ∅ | Server quote total = 0 | Confirmed (free) | Free booking service |
| ∅ | Offline method chosen | Awaiting confirmation | Offline payment service |
| Pending payment | Payment verified + finalized | Confirmed (paid) | BookingFinalizationService |
| Pending payment | Captured but finalization failed | Payment captured — unfulfilled | Webhook/verify; recovery job |
| Payment captured — unfulfilled | Retry succeeds | Confirmed (paid) | Recovery job |
| Payment captured — unfulfilled | Cannot fulfil | Refunded | Refund service (auto) |
| Pending payment | Expiry / failure | Expired | Reservation expiry |
| Awaiting confirmation | Confirmed by authorized role | Confirmed (paid) | Offline payment service, audited |
| Awaiting confirmation | Rejected | Rejected | same |
| Confirmed | POS void approved | Voided | BoxOfficeSaleService (iteration 4) |
| Confirmed | Partial refund | Partially refunded | Refund service (iteration 5) |
| Confirmed / Partially refunded | Full refund | Refunded | Refund service |
| Confirmed | Event cancelled | Cancelled → Refunded | Cancellation service (DEC-04) |

Ticket validity: only Confirmed and Partially refunded (for non-refunded units) bookings carry admissible tickets.

### 7.4 Issued ticket

**Current [VERIFIED]:** `status` = active (only value written); `checked_in_at`, `presence_state` (outside/inside), `entry_count`, `reentry_count` track admission; credential replacement does not change ticket status.

**Canonical [PROPOSED]:**

| From | Event | To |
|---|---|---|
| ∅ | Booking confirmed | Active |
| Active | First ENTRY | Active (checked-in; presence inside) — presence tracked by §7.x Admission |
| Active | Booking voided / ticket revoked by organizer | Revoked |
| Active | Unit refunded | Refunded |
| Active | Event cancelled | Cancelled |
| Active | Event ended + grace | Expired (derived) |
| Active | Token reissued | Active (new token; old token invalid) |
| Revoked / Refunded / Cancelled / Expired | — | terminal |

### 7.5 POS sale

| From | Event | To | Owner |
|---|---|---|---|
| ∅ | Sale committed | Completed | BoxOfficeSaleService [VERIFIED] |
| Completed | Void requested | Completed (void pending) | requestVoid [VERIFIED] |
| Completed (void pending) | Void approved, no scanned tickets | Voided | approveVoid [VERIFIED] |
| Completed | Refund (after scan / after day close) | Refunded / Partially refunded | Refund service [PROPOSED, DEC-42] |

### 7.6 POS hold

| From | Event | To | Owner |
|---|---|---|---|
| ∅ | Hold created | Held | BoxOfficeOperationsController [VERIFIED] |
| Held | Resume | Resumed | [VERIFIED] |
| Held | `expires_at` passed (lazy) | Expired | [VERIFIED] |
| Held / Resumed | Cancel | Cancelled | [VERIFIED] |
| Resumed | Sale completed from hold | Converted | [PROPOSED] |
| Resumed | Not completed within expiry | Expired | [PROPOSED] |

### 7.7 Cash shift

| From | Event | To | Owner |
|---|---|---|---|
| ∅ | Open (one open shift per staff member) | Open | BoxOfficeShiftService [VERIFIED] |
| Open | Close with declared cash | Closed | [VERIFIED] |
| Closed | Verify | Verified | [VERIFIED] |
| Closed | Variance over threshold, reopened for correction | Open | [PROPOSED] |
| Verified | Post-close void/refund | Verified (+ adjustment entry) | [PROPOSED] |

### 7.8 Void request

| From | Event | To |
|---|---|---|
| ∅ | Request with reason | Pending [VERIFIED] |
| Pending | Approve | Approved [VERIFIED] |
| Pending | Reject | Rejected [PROPOSED — not implemented] |

### 7.9 – 7.13
Payment order, Pass entitlement, Credential, Admission, Settlement transfer, Refund — pending their module iterations. Verified current states captured so far for later use:

| Object | States observed in code [VERIFIED] |
|---|---|
| Booking (`paymentStatus`) | pending, completed, free, rejected |
| Payment order | created, paid, captured_unfinalized (webhook) (+ refund_status none/partial/full) |
| Issued ticket | active (+ checked_in_at, presence_state inside/outside) |
| Credential | unassigned, active, lost, revoked (engine also accepts "assigned") |
| Ticket–credential assignment | active, replaced |
| POS sale | completed, voided |
| POS hold | held, resumed, cancelled, expired |
| Void request | (pending), approved |
| Cash shift | open, closed (+ verified_at) |

---

## 8. – 13. Pending sections
Data Model (§8), Validation & Business Rules (§9), Error & Recovery (§10), Security & Audit (§11), External Integrations (§12), Web/Mobile Parity (§13) will be completed per module. Verified inputs already collected: core table list from migrations and `database/schema/mysql-schema.sql`.

## 14. Legacy / Conflicting Implementations Register

Classification: LD local defect · AC architecture conflict · LG legacy conflict · MA missing architecture. Evidence is VERIFIED unless the row says SUSPECTED.

| ID | Sev | Class | Domain | Current code paths | Problem | Canonical behavior | Migration / resolution | Test required before closing |
|---|---|---|---|---|---|---|---|---|
| LC-001 | P0 | LG | Admission | `POST /organizer/check-qrcode` (`OrganizerController@check_qrcode`), `POST /admin/check-qrcode` (`AdminController@check_qrcode`), `/api/organizer/check-qrcode` (`Api\OrganizerScannerController`), `/api/admin/check-qrcode` (`Api\AdminScannerController`); used by organizer PWA (`organizer/pwa`), admin PWA, organizer Flutter app | Mutates `bookings.scanned_tickets` from `booking_id__unique_id`; bypasses issued tickets, credentials, revocation, re-entry, gates, audit | All admission through `AccessControlService` with explicit direction | Convert the four endpoints into adapters calling the unified engine (or return 410), switch PWA and organizer app to `/api/scanner/*`, then remove | Legacy payload rejected or adapted; no write to `scanned_tickets`; revoked credential denied via every path |
| LC-002 | P0 | LD | Admission / Auth | `routes/organizer.php` registers `organizer/check-qrcode` with no middleware | Endpoint reachable without authentication (fails with error when no session, still state-changing for any session) | Authenticated, permission-checked endpoint or removed | Fold into LC-001 | Unauthenticated POST → 401 |
| LC-003 | P0 | AC | RBAC | `StaffAuthController@authenticate` logs in `staff` and `organizer` guards; `EnsureOrganizerStaffRbac` | Staff operate as the organizer: routes outside the RBAC group are fully open; actions attributed to organizer; event/location assignment not enforced in organizer area; organizer POS lets staff sell without shift or staff attribution | Staff never hold an organizer session; staff UI uses staff routes with assignment + permission checks; every write records actor type and id | Introduce a single `Actor` context (organizer / staff / admin) resolved server-side; migrate organizer area routes to accept it; remove organizer login on staff auth | Staff session cannot reach any organizer-only route; staff actions recorded with staff id; staff limited to assigned events |
| LC-004 | P0 | LD | Payments / RBAC | `/organizer/payments-settlements` + `/preference` group has only `auth:organizer` | Staff (via LC-003) can view finance and change settlement preference | Settlement preference organizer-only; finance view requires `payments.view` | Move into RBAC group; deny staff on preference | Staff POST preference → 403 |
| LC-005 | P0 | LG | Checkout | `POST /api/event-booking` (`Api\EventController@store_booking`), customer app `Urls.eventBooking`, `/api/event/checkout-verify`, `/api/event/verify-payment` | Accepts client `total`, `tax`, `paymentStatus`; `gatewayType=online` defaults to `completed`; unauthenticated; customer app has no Payments V2 integration | Mobile checkout uses `/api/v1/payments/razorpay/order` + `/verify`; free bookings through a server free-booking endpoint | Disable client-trusted completion immediately (reject `completed` without server verification); ship customer app on Payments V2; retire legacy endpoints | Forged completed booking rejected; no issued tickets without verified payment or server-confirmed free total |
| LC-006 | P1 | LG | Checkout | `/api/event/checkout-verify`, `/api/event/verify-payment`, 16 web gateway notify routes | Supplier verification flows outside Payments V2 (SUSPECTED trust issues per gateway) | Razorpay via Payments V2 only (DEC-01) | Audit each in iteration 5; disable non-Razorpay gateways in config | Each disabled gateway returns 404/410 |
| LC-007 | P0 | LD | Operations | `GET /migrate` in `web.php` | Public route runs `artisan migrate` (blocked by confirmation only in production env; runs on staging) | Migrations only through deployment tooling | Remove route | Route absent |
| LC-008 | P1 | LD | Operations | `GET /send-ticket`, `/send-push-notification-phone`, `/check-payment` | Public routes start `queue:work` / gateway polling (resource exhaustion, uncontrolled side effects) | Scheduler/cron via CLI | Replace with `schedule:run` cron | Routes absent; queue processed by cron |
| LC-009 | P1 | LD | Admission | `TicketAdmissionService::admit()` (6 params) called with 9 args by organizer/admin/staff scanner controllers | `gate_id`, `override`, `override_reason` silently discarded: gate direction rules, gate logging and supervisor override never work via API | Scanner context reaches the engine intact | Pass through all parameters (or call `AccessControlService` directly); add behavioral test | Exit at entry-only gate denied; override with reason admits and logs `is_override` |
| LC-010 | P2 | MA | RBAC / Access | `organizer_staff_assignments` (event, location only) | Docs claim gate-scoped staff; schema has no gate assignment | Optional gate scope per assignment | Add `gate_id`/`zone_id` to assignments; enforce in engine | Staff scanning at unassigned gate denied |
| LC-011 | P1 | MA | Admission | `AccessControlService::scan` | No event context from scanner; ordinary (non-pass) tickets not checked against event dates or event state | Scanner session declares event (and gate); engine denies wrong event, outside date window, cancelled/archived event | Add `event_id` to scan contract; add date-window rule for non-pass tickets (DEC-11 grace) | Wrong-event and wrong-date scans denied with reason codes |
| LC-012 | P3 | AC | RBAC | `actorCanScan` | `tickets.scan` grants both entry and exit, overriding granular `access.scan_exit` | Granular permissions authoritative; `tickets.scan` = entry only or deprecated | Decide mapping (DEC-16) | Entry-only staff cannot exit-scan |
| LC-013 | P2 | AC | Access / Events | `events.reentry_policy/max_reentries`, `event_access_policies.reentry_policy`, `tickets.reentry_policy` | Three re-entry sources; engine uses ticket → policy, ignores event fields | One authority (DEC-05) | Make event fields defaults for policy; migrate | Re-entry decision matches configured authority |
| LC-014 | P4 | AC | Access | `ticket_admission_logs` and `access_scans` both written | Duplicate audit stores | `access_scans` is the scan ledger | Stop writing the old log after reports migrate | Reports read `access_scans` only |
| LC-015 | P1 | LD | Payments | `PlatformFeeCalculator::calculateRule(int,?Rule,?Profile)` called with `$qty` by `PaymentOrderService` | `per_ticket_amount` charged once per order, not per ticket | Fee = % + fixed + per_ticket × quantity, bounded by min/max | Add quantity parameter; snapshot unaffected for old orders | 3 tickets × ₹10 per-ticket rule → ₹30 fee |
| LC-016 | P1 | AC | Payments / POS | `BoxOfficeSaleService` | POS creates no `payment_orders`, uses own ledger, no tax, no additional fees, fee quantity missing, channel named `box_office` for pricing but `pos` for fees | POS is a sales channel on the same payment-order, fee, tax and ledger engine | Create payment order (channel `pos`, method cash/upi/card/other) per sale; unify ledger; normalize channel vocabulary | POS sale produces payment order + fee lines + ledger entries identical in shape to web |
| LC-017 | P1 | MA | Payments / Inventory | `AuthoritativeTicketPricingService` checks stock; `BookingFinalizationService` decrements after capture | Customer can pay and then fail finalization ("Ticket stock changed before payment completion") with no booking and no automatic refund | Short reservation at order creation with expiry, or paid-but-unfulfilled → automatic refund state | Define reservation model (DEC-20); add recovery job | Last-ticket race: one booking succeeds, the other is refunded automatically |
| LC-018 | P2 | LD | Booking | `BookingFinalizationService` | Hardcodes paymentMethod "Razorpay", currency symbol ₹; `event_date` defaults to today when absent | Values from order snapshot; event date required for multi-date | Fix in iteration 3 | Multi-date booking without date rejected |
| LC-019 | P1 | LD | Admin RBAC | `admin.php`: box-office-settings, organizer-workforce (incl. impersonate), mobile-home routes | No `permission:` middleware — any admin sub-role can impersonate staff and change settings | Explicit admin permissions per module | Add permissions (e.g. "Organizer Workforce", "Box Office Settings", "Mobile Interface") | Sub-admin without permission → 403 |
| LC-020 | P0 | LD | Tickets | `Organizer\TicketController@store/update/destroy/bulk_delete`, `delete_variation`; `TicketRequest::authorize()` returns true | No ownership checks: any organizer (or staff with `tickets.manage`) can create tickets on, edit prices of, or delete tickets of other organizers' events by id | Every ticket mutation verifies ticket → event → organizer | Add ownership guard (policy) to all ticket and seat-map endpoints, web and API | Cross-organizer ticket create/update/delete → 403/404 |
| LC-021 | P0 | LD | Bookings | `Organizer\EventBookingController@bulkDestroy` (no ownership), `@destroy` (hard delete) | Any organizer can delete any booking by id; organizers can hard-delete paid bookings | Commercial records are never hard-deleted; cancellation/refund flows instead | Remove delete; add cancel/archive with audit | Bulk delete of foreign booking id impossible; paid booking cannot be deleted |
| LC-022 | P0 | AC | Events | `Organizer\EventController@destroy`, `bulk_delete`; admin equivalents | Deleting an event hard-deletes all bookings, invoices, tickets | UC-028 archive model | Block delete when commercial records exist | Event with a booking cannot be deleted |
| LC-023 | P1 | LD | Events | `EventController@updateTicketSetting` → `$event->update($request->all())` | Mass assignment of `status`, `organizer_id`, `event_type`, `box_office_enabled`, re-entry fields via ticket settings form; bypasses publish guard and type-change guard | Whitelist ticket presentation fields | Use `only([...])` | Posting `status=1` or `organizer_id` via ticket settings has no effect |
| LC-024 | P2 | AC | Events / Payments | `PaidEventPayoutGuard` used only by `updateStatus` | Contradicts Payments V2 ("sales must not be blocked solely because KYC is incomplete"); also bypassable (create with status 1, ticket settings, organizer API, adding paid tickets to an active event) | Single publish rule (UC-026, DEC-03) applied everywhere | Implement publish service; remove ad-hoc guard | Publish behaves identically on all channels |
| LC-025 | P2 | AC | Events / Mobile | `Api\Organizer\EventController`, organizer app event screens | Parallel event logic: no box office type, venue requires coordinates, separate date delete, no publish guard, no shared service; public location endpoints | API adapters over `EventFormService` (UC-030) | Phased migration UC-030 | Same payload produces same event via web and API |
| LC-026 | P2 | LD | Events | `EventFormService::syncOnlineTicket` (`firstOrNew(['event_id'])`) with type change allowed before bookings | Changing a venue event with several tickets to online overwrites the first ticket and leaves others | Type locked after first ticket; online ticket identified explicitly | Add lock rule; mark online ticket | Type change with tickets → validation error |
| LC-027 | P1 | AC | Events / Passes / Access | `syncDates` deletes unsubmitted `event_dates`; API `event-delete-date` | Sessions with bookings, pass entitlements or admission states can be deleted, orphaning entitlements and breaking admission | Protected sessions cannot be deleted; cancel instead (DEC-04) | Dependency check before delete | Delete of session with entitlement rejected |
| LC-028 | P2 | MA | Events | `EventFormService::duplicateEvent`; `syncBoxOfficeLocations` name-keyed delete | Duplicate omits passes, locations, access policy, gates, zones, additional fees; copies remaining stock and past dates. Renaming a location deletes the old row | UC-025 canonical copy set; locations deactivated not deleted | Extend duplicate; location ids in form | Duplicate of full event reproduces configuration with reset inventory |
| LC-029 | P3 | MA | Events | `events.status` varchar 0/1; `status` unvalidated | No lifecycle (draft/paused/cancelled/archived) | §7.1 | DEC-10 | State transitions enforced |
| LC-030 | P2 | MA | Bookings / Payments | `EventBookingController@updatePaymentStatus` (organizer and admin) | Manual change of payment status (offline approval) creates ticket entitlement without payment-order/ledger record or audit | Offline payment confirmation is an audited payment-order transition (DEC-01) | Iteration 3/5 | Approval writes ledger and audit |
| LC-031 | P2 | LD | POS | `BoxOfficeSaleService`, `box_office_settings.allow_*` | POS payment-method toggles and required-field settings not enforced at sale | Settings enforced server-side | Iteration 4 | Disabled method rejected |
| LC-032 | P2 | MA | POS | `BoxOfficeOperationsController@hold/resume` | Holds do not reserve inventory, cannot hold passes, organizer-only (no staff hold routes) | DEC-21 hold semantics; staff parity | Iteration 4 | Per decision |
| LC-033 | P2 | AC | POS / Tickets | `BoxOfficeController@approveVoid` | Void sets booking `rejected` but leaves issued tickets `active` and credentials active; organizer can request and approve own void | Void revokes issued tickets and credentials; approver ≠ requester for staff | Iteration 4 | Voided ticket and its wristband denied at gate |
| LC-034 | P1 | AC | POS | Organizer POS routes reachable by staff (LC-003) | Staff sales unattributed and shift-free | Staff sell only through staff POS | Covered by LC-003 | — |
| LC-035 | P2 | LD | RBAC / Access | `EnsureOrganizerStaffRbac` maps all `organizer.access.*` to `credentials.inventory`; controller records actor as organizer | Credential Issuer role cannot use the collection desk; replacement/assignment audit shows organizer | Per-action permissions: assign → `credentials.issue`, replace → `credentials.replace`, batches → `credentials.inventory`, policy/gates → DEC-16 | Iteration 7 | Issuer can assign; audit shows staff id |
| LC-036 | P2 | MA | Access / Payments | `CredentialReplacementService` stores `charge_amount`, `payment_reference` | Replacement fee never reaches payment orders or ledger | Fee collected through POS/payment engine as additional fee | Iteration 5/7 | Replacement fee appears in ledger |
| LC-037 | P3 | MA | Access | No revoke-only route though `credentials.revoke` exists | Cannot revoke a credential without issuing a replacement | Revoke flow with reason | Iteration 7 | Revoked credential denied |
| LC-038 | P2 | LG | Settlement | `withdraws`, `organizers.amount`, `transactions`, organizer Withdraw/Transactions screens, organizer app withdraw | Supplier wallet balance and withdrawals parallel to Payments V2 settlement | Payments V2 ledger + transfers are the only settlement truth | Freeze wallet, migrate balances, retire screens (DEC-01) | No new wallet entries after cut-over |
| LC-039 | P2 | LG | Payments | 16 gateway controllers for event bookings, shop and AI token purchases | Supplier gateways outside Payments V2 | Razorpay via Payments V2 (DEC-01) | Disable, then remove | Only approved gateways reachable |
| LC-040 | P4 | LG | Shop | `/shop/*`, product orders, customer "My orders" | Supplier merchandise shop | DEC-01 | Decide keep/remove | — |
| LC-041 | P2 | MA | QA | `tests/Unit/*` | Most tests assert strings exist in source files rather than behavior; did not catch LC-009/LC-015 | Behavioral feature tests on staging DB | Build test contracts per UC (§16) | — |
| LC-042 | P2 | MA | Privacy | `bookings.aadhaar_number_encrypted`, `aadhaar_document`, `customer_photo` | Government ID data collected at POS; retention, access and legal basis undefined | DEC-22 | — | — |
| DEC-23 | Variation identity | Name (current) / stable ids | Child table with ids; names editable |
| DEC-24 | Ticket inventory per date for multi-date events | Shared (current) / per date | Per date for date-bound tickets; shared only for "any date" passes |
| DEC-25 | Does a pass also consume its base ticket's stock? | Both (current) / pass only | Pass only; base ticket used for presentation and admission defaults |
| DEC-26 | Ticket sales windows | None / start-end per product | Add per product |
| DEC-27 | Seat maps in POS and app | Web only / all channels | All channels via server seat reservation |
| DEC-28 | Pass selection modes | fixed = all (current) | `all_dates` dynamic; `fixed_dates` linked subset |
| DEC-29 | Stock restoration on refund/cancel | Never (current, except POS void) / always / policy | Restore when refund completes before event start |
| DEC-30 | `admissions_per_holder` meaning | Unused / N people per credential / N entries | Number of persons admitted per scan (group/couple), tracked as a counter |
| DEC-31 | Guest access to tickets after checkout | Email only / signed retrieval link / lookup by email + reference + OTP | Signed link in email + OTP lookup |
| DEC-32 | Ticket transferability | None / free transfer / organizer-controlled | Organizer setting per ticket type; transfer reissues token |
| DEC-33 | Purchase limit semantics | Per order / per customer per event (legacy, logged-in only) | Per order always; optional per-customer cap for logged-in buyers |
| DEC-34 | Public booking reference | `uniqid()` / random code | Random, non-sequential, human-friendly code |
| DEC-35 | Tax model | Single platform rate (current `basic_settings.tax`) / per organizer GSTIN / per event | Define with finance (GST place-of-supply rules) |
| DEC-36 | Named attendees per ticket | Booker only (current) / per-ticket names | Optional per event; required when identity checks or transfers apply |
| DEC-37 | Past events on public pages | Hide / show as past | Show as past, non-sellable, for SEO and organizer pages |
| DEC-38 | Organizer sale notifications | None / email / push / digest | Configurable digest + real-time push in organizer app |
| DEC-39 | Ticket token key management | `APP_KEY` derived (current) / dedicated rotating key with key id | Dedicated ticket-signing key with key id in token |
| DEC-40 | POS UPI/card payment confirmation | Operator attestation (current) / dynamic UPI QR / Razorpay POS device | Dynamic UPI QR via Razorpay for UPI; card terminal reference mandatory |
| DEC-41 | Shifts for organizer-operated sales | Not required (current) / required | Required whenever cash is accepted |
| DEC-42 | Void and refund rules at POS | Void only before scan (current) | Void before scan same day; otherwise refund flow (iteration 5) with method-specific handling |
| DEC-43 | Staff assignment model | One event (current) / many events, gates, date ranges | Many; gate/zone optional; date range optional |
| DEC-44 | Who may manage the team | Organizer + `team.manage` staff (current via LC-003) | Organizer only, or staff with `team.manage` limited to permissions they hold |
| LC-043 | P3 | LG | Scanner app | `scanner-app` calls `/ticket/scanned-status-change` | Calls retired endpoint (returns 409) | Remove call | App update | — |
| LC-044 | P0 | LG | Organizer app | Organizer app scans via `/api/organizer/check-qrcode` | Same as LC-001 on mobile | Use scanner API | Covered by LC-001 | — |
| LC-045 | P2 | LD | Events / Media | `imagermv`, `imagedbrmv` (no ownership), `attachGallery` (any unattached id) | Organizer can delete or claim other organizers' gallery images by id | Ownership/upload-session scoping | Store uploader on `event_images` | Foreign image delete → 403 |
| LC-046 | P0 | LD | Checkout | `CheckOutController@checkout2` (online events: session total from client `pricing_type`), `BookingController@index` free branch (event id from client `event` JSON, quantity from request) | VERIFIED by code reading: posting `pricing_type=free` for a paid online event sets the session total to 0 and the free branch confirms a `free` booking with issued tickets; free branch also trusts client event id and quantity. Staging reproduction still recommended | Free status decided only by the server quote for the route's event | Replace free branch with server quote (UC-048) | Paid event cannot be booked free by request tampering |
| LC-047 | P1 | AC | Tickets / Passes | `TicketController@destroy/bulk_delete`; FK cascades `event_pass_products.ticket_id`, `pass_entitlements.pass_product_id` | Deleting a ticket (no sales check) cascades to pass products and the entitlements of already-sold passes; holders are then refused admission | Sold tickets are retired, never deleted; passes protect their base ticket | Retire state + dependency checks; change FKs to restrict | Sold ticket cannot be deleted; entitlements survive |
| LC-048 | P1 | AC | Seat maps / Payments | `RazorpayController@bookingProcess` (V2) drops seat data; `BookingFinalizationService` stores no `seat_id`; `BookingServices::getBookingDeactiveData` derives booked seats from booking JSON | Seats bought through Payments V2 are not marked booked → same seat can be sold again; seats priced at ticket price, not seat price | Server-priced, server-reserved seats on every channel | Add seat lines to the quote, snapshot and finalization; seat reservation table | Same seat cannot be bought twice; seat price charged |
| LC-049 | P2 | AC | Access / Tickets | `tickets.admission_pass_type` default `mobile_qr`; engine treats any non-empty value as ticket-level config | Event access policy settings never apply to tickets created after the migration | DEC-05 precedence | Null default + explicit inheritance flag | Policy settings apply when ticket inherits |
| LC-050 | P2 | LD | Tickets / Payments / POS | `AuthoritativeTicketPricingService`, `BoxOfficeSaleService` | Ticket and variation max-per-order not enforced (API caps 50 per line) | Enforced on every channel | Add to quote | Over-limit order rejected on web, app, POS |
| LC-051 | P2 | AC | Tickets | `tickets.ticket_available` edited directly | Organizer edits remaining stock, not capacity; sold count unknown; duplicate copies remaining stock | Capacity + sold (DEC-08) | Add `capacity`, `sold_count`; backfill from issued tickets | Capacity edits never go below sold |
| LC-052 | P3 | LD | Seat maps | `rand(000000, 999999)` slot ids in ticket create/update | Collision risk across tickets/events | Unique generated identifiers | Use UUID / unique constraint | — |
| LC-053 | P2 | LD | Access / Tickets | Ticket form, policy form, batch form, pass form enumerations; `CredentialAssignmentService` | Inconsistent credential type lists (`rfid_wristband` cannot be batched); assignment ignores type match | One enumeration; assignment checks type | Shared enum/config | Mismatched credential rejected |
| LC-054 | P2 | MA | Passes / Access | `event_pass_products.admissions_per_holder`, `credential_types` | Stored but not used by admission or assignment; group/couple passes admit one person | Defined semantics (DEC-30) | Per decision | — |
| LC-055 | P3 | MA | Access | `tickets.collection_required`, `exit_scan_required` | Stored but not enforced | Defined semantics | Enforce in engine or remove | — |
| LC-056 | P0 | LD | Seat maps | `Organizer\SlotSeatController` (all actions) | No ownership checks: organizers can alter other organizers' seat maps and seat prices by id | Ownership verified via ticket → event → organizer | Add guard | Cross-organizer seat edit → 403 |
| LC-057 | P1 | AC | Discounts / Payments | `FrontEnd\EventController@applyCoupon` (session) vs `AuthoritativeTicketPricingService` | Coupon accepted in checkout UI but ignored by the V2 quote (amount charged differs from what was applied — SUSPECTED display mismatch to verify) | Discounts evaluated in the quote (DEC-06) | Iteration 5 | Charged total equals displayed total |

---

### 14.1 Implementation status (branch `fix/web-hardening-2026-10`, 7 Oct 2026)

Fixed in web code with behavioural tests (`source/website/tests/Feature/Hardening`): LC-001, LC-002, LC-003 (actions scoped; lists still unfiltered), LC-004, LC-005, LC-007, LC-008, LC-009, LC-011, LC-015, LC-016 (pos/box_office channel alias only), LC-018, LC-019, LC-020, LC-021, LC-022, LC-023, LC-026, LC-027, LC-028, LC-030, LC-031, LC-033, LC-034, LC-035, LC-037, LC-044 (server side), LC-045, LC-046, LC-047, LC-048, LC-050, LC-053, LC-056, LC-057, LC-058, LC-059, LC-061, LC-062, LC-063, LC-064, LC-065, LC-066, LC-069, LC-070, LC-072, LC-073, LC-074, LC-075, LC-076, LC-077, LC-080, LC-082.

Open (decision or mobile release needed): LC-006, LC-010, LC-012, LC-013, LC-014, LC-017, LC-024, LC-025, LC-029, LC-032, LC-036, LC-038 – LC-043, LC-049, LC-051, LC-052, LC-054, LC-055, LC-060, LC-067, LC-068, LC-071, LC-078, LC-079, LC-081. Deployment steps: `docs/WEB-HARDENING-2026-10.md`.

## 15. Open Architecture Decisions

| ID | Decision required | Options | Architect recommendation |
|---|---|---|---|
| DEC-01 | Scope of supplier-era modules: Shop, 16 gateways, offline gateway, wallet/withdrawals, blog | Keep / hide / remove | Razorpay via Payments V2 only; offline payment only as audited admin/organizer confirmation; retire wallet; hide shop until a product decision |
| DEC-02 | Is "box office" an event type, a capability of venue events, or both? | Type only / capability only / both (current) | Keep type `box_office`; keep `box_office_enabled` as capability for venue events; POS available when either is true |
| DEC-03 | Can paid events publish before organizer KYC is activated? | Block (current guard) / allow with BookTKIT Managed (Payments V2 doc) | Allow; settlement held in Managed until KYC; show organizer banner outside POS |
| DEC-04 | Session/event cancellation and refund policy | Manual / automatic refunds / credit | Define cancellation flow with automatic refund of affected bookings |
| DEC-05 | Single re-entry authority | Event / access policy / ticket | Ticket overrides access policy; access policy defaults from event; remove event-level use |
| DEC-06 | Coupons and discounts in Payments V2 | Keep supplier coupons / redesign | Redesign as server discount rules snapshotted on payment orders |
| DEC-07 | Who initiates refunds (customer request, organizer, admin) | — | Customer/organizer request, admin/finance approve, executed through Payments V2 |
| DEC-08 | Ticket capacity model | Mutable remaining stock (current) / capacity + sold count | Capacity + sold count (immutable capacity, derived availability) |
| DEC-09 | Notify attendees on event changes | Never / material changes | Notify on date, location, meeting link changes and cancellation |
| DEC-10 | Event lifecycle states | Keep 0/1 / §7.1 | Adopt §7.1 |
| DEC-11 | Admission grace windows (early entry, late scans) | — | Configurable per event: open X minutes before session, close at session end |
| DEC-12 | Dedicated finance role | Admin permission / separate actor | Admin permission set "Finance" separate from "Transaction" |
| DEC-13 | Who controls "featured" | Organizer / admin / paid | Admin only; organizer may request |
| DEC-14 | Separate staff permission for publishing | — | Add `events.publish` |
| DEC-15 | Can admins sell through POS? | — | No; admins impersonate staff for support only |
| DEC-16 | Permissions for access configuration and legacy `tickets.scan` | — | Add `access.configure`; `tickets.scan` = entry only, deprecate |
| DEC-17 | Server-side event drafts | Local only (current) / server draft | Server Draft state (§7.1) replaces local-only draft |
| DEC-18 | Event timezone | Implicit server/local | Store event timezone; all session times interpreted in it |
| DEC-19 | Platform-owned events (organizer_id null) | Keep / disallow | Disallow new; map existing to a BookTKIT organizer account |
| DEC-20 | Inventory reservation during payment | None (current) / time-boxed reservation | Time-boxed reservation (e.g. 10 minutes) at order creation |
| DEC-21 | POS hold semantics | Saved cart (current) / inventory reservation | Reserve inventory until expiry; staff parity |
| DEC-22 | Government ID collection at POS | Keep / restrict / remove | Restrict to events legally requiring it; last-4 + encrypted document with retention period and access logging |

---

## 16. Acceptance Criteria

## 16.1 Acceptance Criteria — Event domain

| AC ID | Use case | Criterion |
|---|---|---|
| AC-EVT-01 | UC-018 | Creating an online event with no address succeeds; with no meeting URL fails. |
| AC-EVT-02 | UC-018 | Creating a venue event without coordinates succeeds on web and API. |
| AC-EVT-03 | UC-018 | Creating a box office event with zero locations fails; with one location succeeds and enables POS for that event. |
| AC-EVT-04 | UC-018 | An organizer-submitted `organizer_id` is ignored/prohibited. |
| AC-EVT-05 | UC-018 | A newly created event is Draft regardless of submitted status (after DEC-10). |
| AC-EVT-06 | UC-019 | Saving a single-date event removes all `event_dates` rows; saving multiple-date event nulls event-level dates. |
| AC-EVT-07 | UC-019 | Removing a session with bookings, pass entitlements or admission states is rejected. |
| AC-EVT-08 | UC-021 | Renaming a box office location preserves its id and historical references. |
| AC-EVT-09 | UC-023 | Ticket settings cannot change status, organizer, type or re-entry fields. |
| AC-EVT-10 | UC-024 | Event type change is rejected after the first ticket (and after bookings). |
| AC-EVT-11 | UC-025 | Duplicate produces a Draft event with copied dates, tickets (reset inventory), passes, locations, access policy, gates, zones and additional fees, and no commercial records. |
| AC-EVT-12 | UC-026 | Publish readiness is identical on organizer web, admin and organizer API. |
| AC-EVT-13 | UC-026 | Unpublishing does not change any issued ticket's validity. |
| AC-EVT-14 | UC-028 | An event with any booking, payment order, POS sale, issued ticket or credential cannot be hard-deleted. |
| AC-EVT-15 | UC-016 | Staff with `events.view` see only assigned events. |
| AC-EVT-16 | UC-022 | An organizer cannot delete or attach another organizer's gallery image. |
| AC-EVT-17 | UC-030 | The same payload through organizer API and web produces identical event records. |

---

## 16.2 Acceptance Criteria — Tickets & Passes

| AC ID | Use case | Criterion |
|---|---|---|
| AC-TKT-01 | UC-031/032/033 | An organizer cannot create, edit or delete tickets of another organizer's event (web and API). |
| AC-TKT-02 | UC-033 | A ticket with sales or pass dependencies cannot be deleted; it can be retired. |
| AC-TKT-03 | UC-032 | Price change after sale leaves existing orders, bookings and issued tickets unchanged. |
| AC-TKT-04 | UC-034 | A physical-credential ticket with QR-before-assignment disabled is refused at the gate until a credential is assigned; with a credential active, the ticket QR is refused. |
| AC-TKT-05 | UC-034 | Re-entry `limited` with max 2: entries 1–3 admitted (with exits between), 4th denied "Re-entry limit reached". |
| AC-TKT-06 | UC-035 | Early bird applies until the deadline and not after; snapshot preserved on late verification. |
| AC-TKT-07 | UC-036 | A seat sold on any channel cannot be sold again; charged price equals seat price. |
| AC-TKT-08 | UC-038/040 | `choose_n` pass with N=2 requires exactly two eligible dates; the issued ticket has two entitlements; admission on a third date is denied "This pass is not valid for today". |
| AC-TKT-09 | UC-039 | A sold pass cannot be deleted; disabling stops new sales only. |
| AC-TKT-10 | UC-042 | Concurrent last-unit purchase across web and POS results in exactly one sale. |
| AC-TKT-11 | UC-031/042 | Max per order enforced on web, app and POS. |
| AC-TKT-12 | UC-041 | Charged amount equals the total displayed after coupon application. |

## 16.3 Acceptance Criteria — Booking, Checkout, Issuance & Delivery

| AC ID | Use case | Criterion |
|---|---|---|
| AC-BKG-01 | UC-048 | A paid online event cannot be booked as free by altering `pricing_type`, `total` or the `event` JSON. |
| AC-BKG-02 | UC-049 | Amount charged by Razorpay equals the payment order `customer_total` computed by the server. |
| AC-BKG-03 | UC-049 | Repeating the notify/verify call never creates a second booking or ledger entry. |
| AC-BKG-04 | UC-049 | Closing the browser after payment still results in a booking (webhook/job) within the recovery SLA. |
| AC-BKG-05 | UC-049 | If stock runs out between quote and payment, the customer is automatically refunded and informed. |
| AC-BKG-06 | UC-050 | Posting `paymentStatus=completed` to the legacy API does not create a confirmed booking. |
| AC-BKG-07 | UC-050 | API orders ignore a client-supplied customer id. |
| AC-BKG-08 | UC-051 | Offline booking confirmation records approver, time and reference; rejection releases inventory. |
| AC-BKG-09 | UC-052 | An unauthenticated visitor cannot view another buyer's confirmation or QR codes by changing ids. |
| AC-BKG-10 | UC-053 | A customer sees only own bookings on web and app; refunded/voided tickets show as not valid. |
| AC-BKG-11 | UC-044/049 | Quotes for unpublished, ended or invalid-date events are rejected on every channel. |
| AC-ISS-01 | UC-096 | Concurrent views of a confirmed booking create exactly one issued ticket per unit. |
| AC-ISS-02 | UC-096 | A pass with three selected dates creates three entitlements per issued ticket. |
| AC-ISS-03 | UC-097 | A token whose signature is altered is denied "Ticket not found". |
| AC-ISS-04 | UC-098 | SMTP failure does not roll back the booking; delivery status shows failed; resend succeeds. |
| AC-ISS-05 | UC-098 | Ticket PDFs are not downloadable without authorization. |


## 16.4 Acceptance Criteria — POS / Box Office and Team

| AC ID | Use case | Criterion |
|---|---|---|
| AC-POS-01 | UC-077 | Submitting the same `sale_uuid` twice creates one sale, one booking and one set of tickets. |
| AC-POS-02 | UC-077 | Staff cannot sell without an open shift for the same event and counter; organizer rule per DEC-41. |
| AC-POS-03 | UC-077 | A staff sale is attributed to the staff member on every route. |
| AC-POS-04 | UC-075/077 | POS total equals the online total for the same cart, method-specific fees aside (tax and additional fees included). |
| AC-POS-05 | UC-076 | A payment method disabled in POS settings is rejected by the server. |
| AC-POS-06 | UC-074 | Customer field rules are identical on organizer and staff POS and follow settings. |
| AC-POS-07 | UC-080 | A held cart reserves inventory until expiry (after DEC-21) and requires email. |
| AC-POS-08 | UC-087 | Approving a void restores ticket, variation and pass stock, revokes issued tickets and credentials, reverses the ledger and adjusts the shift. |
| AC-POS-09 | UC-087 | A sale with a scanned ticket cannot be voided. |
| AC-POS-10 | UC-084 | Expected cash = opening cash + cash sales − cash voids/refunds in the shift. |
| AC-POS-11 | UC-071 | A phone-width POS completes a sale without horizontal scrolling. |
| AC-TEAM-01 | UC-089 | A ticket checker can be assigned to a venue event without box office. |
| AC-TEAM-02 | UC-089/090 | An actor cannot grant permissions they do not hold. |
| AC-TEAM-03 | UC-092 | A staff member with a pending password change cannot use any feature on any route until changed. |
| AC-TEAM-04 | UC-093 | Archiving revokes web sessions and API tokens immediately; history remains. |
| AC-TEAM-05 | UC-095 | Impersonation requires an admin permission and is visibly bounded and audited. |

---

## 17. Traceability Matrix

## 17.1 Traceability Matrix — Event domain

| UC | Screens | Routes | Controller | Request / Service | Tables | Conflicts | Tests (current) |
|---|---|---|---|---|---|---|---|
| UC-016 | ORG-EVT-001, APP-ORG events | GET `/organizer/event-management/events`; `/api/organizer/event-management/events` | `Organizer\EventController@index`; `Api\Organizer\EventController@index` | — | events, event_contents | LC-003, LC-025 | `OrganizerDashboardQaRegressionTest` (string checks) |
| UC-017 | ORG-EVT-002 | GET `/organizer/choose-event-type` | `@choose_event_type` | — | — | LC-025 | — |
| UC-018 | ORG-EVT-003 | GET `/organizer/add-event`; POST `/organizer/event-store` | `@add_event`, `@store` | `EventFormRequest`, `EventFormService::createEvent` | events, event_contents, event_dates, tickets, box_office_locations, event_images | LC-024, LC-029, LC-045 | `EventFormServiceTest` |
| UC-019 | ORG-EVT-003/004 | as UC-018/UC-024; `/api/.../event-delete-date` | — | `syncDates` | event_dates | LC-027 | — |
| UC-020 | ORG-EVT-003/004 | as UC-018 | — | `syncContents`, `syncOnlineTicket` | event_contents, events, tickets | LC-026 | — |
| UC-021 | ORG-EVT-003/004 | as UC-018 | — | `normalizeEventData`, `syncBoxOfficeLocations` | events, box_office_locations | LC-013, LC-028 | `SpecialEventPhase2Test` |
| UC-022 | ORG-EVT-003, ORG-EVT-006 | POST `event-imagesstore`, `event-imagermv`, `event-img-dbrmv`; AI routes | `@gallerystore`, `@imagermv`, `@imagedbrmv`, `AiImageController` | `processThumbnail`, `attachGallery` | event_images, events | LC-003, LC-045 | — |
| UC-023 | ORG-EVT-005 | GET/POST ticket setting | `@editTicketSetting`, `@updateTicketSetting` | `TicketSettingRequest` | events | LC-023 | — |
| UC-024 | ORG-EVT-004 | GET `/organizer/edit-event/{id}`; POST `/organizer/event-update` | `@edit`, `@update` | `EventFormService::updateEvent` | as UC-018 | LC-025, LC-026, LC-027 | `EventFormServiceTest` |
| UC-025 | ORG-EVT-001 | POST `/organizer/duplicate-event/{id}` | `@duplicate` | `EventFormService::duplicateEvent` | events, event_contents, event_dates, event_images, tickets | LC-028 | `EventFormServiceTest` |
| UC-026 | ORG-EVT-001, ORG-EVT-003 | POST `/organizer/event/{id}/update-status`; `/api/.../event-update-status` | `@updateStatus` | `PaidEventPayoutGuard` | events, organizer_payment_profiles | LC-023, LC-024, LC-029 | — |
| UC-027 | ORG-EVT-001 | POST `/organizer/event/{id}/update-featured` | `@updateFeatured` | — | events | DEC-13 | — |
| UC-028 | ORG-EVT-001 | POST `/organizer/delete-event/{id}`, `bulk/delete/event` | `@destroy`, `@bulk_delete` | — | events + dependents | LC-022 | — |
| UC-029 | ADM-EVT-* | `/admin/*event*` | `BackEnd\Event\EventController` | `EventFormService` (admin actor) | as UC-018 | DEC-19 | — |
| UC-030 | APP-ORG event screens | `/api/organizer/event-management/*` | `Api\Organizer\EventController` | none (divergent) | as UC-018 | LC-025 | — |

---


## 17.2 Traceability Matrix — Tickets & Passes

| UC | Screens | Routes | Controller | Service / request | Tables | Conflicts | Tests (current) |
|---|---|---|---|---|---|---|---|
| UC-031 | ORG-TKT-002 | GET `event/add-ticket`; POST `event/ticket/store-ticket`; API `store-ticket` | `Organizer\TicketController@store`; `Api\Organizer\TicketController@store` | `TicketRequest` | tickets, ticket_contents, variation_contents | LC-020, LC-052 | — |
| UC-032 | ORG-TKT-003 | GET `event/edit/ticket`; POST `ticket_management/update/ticket` | `@update` | `TicketRequest` | as UC-031 | LC-020, LC-051 | — |
| UC-033 | ORG-TKT-001 | POST `event/ticket/delete-ticket`, bulk; GET `delete-variation/{id}` | `@destroy`, `@bulk_delete`, `@delete_variation` | — | tickets (+cascades) | LC-020, LC-047 | — |
| UC-034 | ORG-TKT-002/003 | as UC-031/032 | `applyAdmissionSettings` | `AccessControlService` (consumer) | tickets | LC-049, LC-053, LC-055 | `AccessControlContractTest` (string checks) |
| UC-035 | ORG-TKT-002/003 | as UC-031/032 | — | `AuthoritativeTicketPricingService` | tickets | DEC-18 | — |
| UC-036 | ORG-TKT-004, PUB-EVT-003 | `/organizer/seat-mapping/*`; `/event/slot-mapping-seat` | `SlotSeatController` | `BookingServices` | slots, slot_seats, slot_images | LC-048, LC-056 | — |
| UC-037 | ORG-EVT-003/004 | as UC-018/024 | — | `EventFormService::syncOnlineTicket` | tickets | LC-026 | — |
| UC-038 | ORG-PASS-001 | GET/POST `/organizer/events/{id}/passes` | `EventPassController` | `EventPassService` | event_pass_products, event_pass_dates | LC-054 | `SpecialEventPhase2Test` |
| UC-039 | ORG-PASS-001 | PUT/DELETE `/organizer/events/{id}/passes/{passId}` | `EventPassController` | — | as UC-038 | LC-047 | — |
| UC-040 | PUB-EVT-002, PUB-CHK-001, ORG-POS-001 | checkout, `/api/v1/payments/razorpay/order`, POS sale | Razorpay, PaymentController, BoxOffice | `EventPassService::quote/consume`, `TicketIssuanceService` | pass_entitlements, issued_tickets | LC-005 | — |
| UC-041 | ADM-CPN-001, PUB-CHK-001 | `/admin/event-booking/settings/coupons`; `/event-booking/apply-coupon`; `/api/event/apply-coupon` | `CouponController`, `FrontEnd\EventController@applyCoupon` | — | coupons | LC-057 | — |
| UC-042 | PUB-EVT-001/002, ORG-POS-001 | — | — | `EventStartingPriceService`, `AuthoritativeTicketPricingService`, `LockedTicketInventoryService`, `BookingFinalizationService`, `EventPassService` | tickets, event_pass_products | LC-017, LC-050, LC-051 | `EventStartingPriceServiceTest` |

## 17.3 Traceability Matrix — Booking, Checkout, Issuance & Delivery

| UC | Screens | Routes | Controller | Service | Tables | Conflicts |
|---|---|---|---|---|---|---|
| UC-043 | PUB-HOME-001, PUB-EVT-001, APP-CUS home | `/`, `/events`; `/api/get-basic`, `/api/events` | `FrontEnd\HomeController`, `FrontEnd\EventController`, `Api\EventController` | `EventStartingPriceService` | events, event_contents, tickets, event_pass_products | DEC-37 |
| UC-044 | PUB-EVT-002 | `/event/{slug}/{id}`; `/api/event-details` | `FrontEnd\EventController@details` | — | as above | LC-061 |
| UC-045 | PUB-EVT-002 | POST `/check-out2` | `CheckOutController@checkout2` | `EventPassService::quote`, helpers `StockCheck`, `isTicketPurchaseOnline` | session | LC-046, LC-050 |
| UC-046 | PUB-EVT-003 | `/event/slot-mapping-seat` | `FrontEnd\EventController` | `BookingServices` | slots, slot_seats, bookings | LC-048 |
| UC-047 | PUB-CHK-001 | GET `/checkout` | `CheckOutController@checkout` | — | session | LC-057 |
| UC-048 | PUB-CHK-002 | POST `/ticket-booking/{id}` | `BookingController@index/storeData` | `TicketDeliveryService` | bookings, tickets, issued_tickets | LC-046, LC-069 |
| UC-049 | PUB-CHK-001/002 | POST `/ticket-booking/{id}` (gateway razorpay); `/event-booking/razorpay/notify` | `RazorpayController@bookingProcess/notify` | Pricing, PaymentOrder, RazorpayRoute, BookingFinalization, PaymentLedger | payment_orders, bookings, payment_ledger_entries, payment_transfers | LC-048, LC-057, LC-061 – LC-065 |
| UC-050 | APP-CUS checkout | `/api/event-booking`, `/api/event/verify-payment`; canonical `/api/v1/payments/razorpay/order|verify` | `Api\EventController@store_booking`; `Api\PaymentController` | as UC-049 (v1) | as UC-049 | LC-005, LC-066, LC-067 |
| UC-051 | PUB-CHK-001, ORG-BKG-002, ADM-BKG-002 | POST `/ticket-booking/{id}` (offline); `/organizer/event-booking/{id}/update/payment-status` | `OfflineController`, `EventBookingController@updatePaymentStatus` | — | bookings | LC-030, LC-072 |
| UC-052 | PUB-CHK-002 | GET `/event-booking-complete` | `BookingController@complete` | `TicketIssuanceService` | bookings, issued_tickets | LC-058 |
| UC-053 | CUS-BKG-001, CUS-TKT-001, APP-CUS bookings | `/customer/my-bookings`, `/customer/booking/details/{id}`; `/api/customers/bookings`, `/booking/details` | `CustomerBookingController`, `Api\CustomerController` | `TicketIssuanceService` | bookings, issued_tickets | LC-067, LC-070 |
| UC-054 | CUS-WSH-001 | `/customer/wishlist`; `/api/customers/wishlists/*` | Wishlist controllers | — | wishlists | — |
| UC-055 | — | — | — | — | — | DEC-07, DEC-32 |
| UC-096 | all ticket views | (internal) | — | `TicketIssuanceService` | issued_tickets, pass_entitlements | LC-058, LC-070 |
| UC-097 | ticket views, PDF | — | — | `TicketIssuanceService::tokenForUuid` | issued_tickets | LC-001, DEC-39 |
| UC-098 | email | — | — | `TicketDeliveryService`, `BookingInvoiceJob` | bookings.invoice | LC-059, LC-064 |
| UC-099 | — | — | — | — | — | — |
| UC-100 | ORG-POS-002, STF-POS-002 | POS print | BoxOffice controllers | — | — | iteration 4 |

## 17.4 Traceability Matrix — POS / Box Office and Team

| UC | Screens | Routes | Controller | Service | Tables | Conflicts |
|---|---|---|---|---|---|---|
| UC-071 – UC-073 | ORG-POS-001, STF-POS-001 | GET `/organizer/box-office`, `/staff/box-office` | `BoxOfficeController@index`, `StaffBoxOfficeController@index` | — | events, box_office_locations, tickets, event_pass_products | LC-003, LC-077, LC-078 |
| UC-074 – UC-077 | ORG-POS-001, STF-POS-001 | POST `/organizer/box-office/sales`, `/staff/box-office/sales` | `@store` | `BoxOfficeSaleService`, `AuthoritativeTicketPricingService`, `LockedTicketInventoryService`, `EventPassService`, `PaymentFeeRuleResolver`, `PlatformFeeCalculator`, `TicketIssuanceService`, `BoxOfficeLedgerService` | bookings, box_office_sales, box_office_sale_logs, box_office_ledger_entries, issued_tickets, pass_entitlements | LC-015, LC-016, LC-031, LC-042, LC-076 |
| UC-078, UC-086 | ORG-POS-002, STF-POS-002 | GET `…/sales/{id}/print`, POST `…/reprint` | `@print`, `@reprint` | `TicketIssuanceService` | box_office_sale_logs | — |
| UC-079 | ORG-POS-002 | (part of sale) | — | `TicketDeliveryService` | bookings | LC-064 |
| UC-080 – UC-082 | ORG-POS-001 | GET/POST `/organizer/box-office/holds`, `…/{id}/resume`, DELETE `…/{id}` | `BoxOfficeOperationsController` | — | box_office_holds | LC-032 |
| UC-083 – UC-085 | STF-SHIFT-001, ORG-POS-004 | `/staff/shifts*`, `/organizer/box-office/shifts*` | `StaffShiftController`, `BoxOfficeShiftController` | `BoxOfficeShiftService` | box_office_shifts | LC-079 |
| UC-087 | ORG-POS-005 / sale list | POST `…/void-request`, `…/void-approve` | `BoxOfficeController` | `LockedTicketInventoryService`, `BoxOfficeLedgerService` | box_office_void_requests, box_office_sales, bookings | LC-033, LC-073 |
| UC-088 | ORG-POS-003, ORG-POS-005, ADM-BO-001 | `/organizer/box-office/settings`, `/organizer/box-office/reports`, `/admin/box-office-settings` | `BoxOfficeOperationsController`, `BoxOfficeReportController`, `AdminBoxOfficeSettingsController` | — | box_office_settings | LC-019, LC-031 |
| UC-089 – UC-093 | ORG-TEAM-001 | `/organizer/team*` | `Organizer\StaffController` | — | organizer_staff, organizer_staff_assignments, staff_audit_logs | LC-074, LC-075, LC-081 |
| UC-094 – UC-095 | ADM-TEAM-001 | `/admin/organizer-workforce*` | `OrganizerWorkforceController` | — | organizer_staff, staff_audit_logs | LC-019, LC-082 |

---

*End of iteration 4. Next iteration: Payments, fees, settlement, refunds and organizer onboarding/KYC (UC-011 – UC-015, UC-056 – UC-070).*
