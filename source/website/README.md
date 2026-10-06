# BookTKIT Laravel Website / Shared Backend

This is the canonical Laravel 9.x / PHP 8.3 backend for BookTKIT, not a generic Laravel starter.

It serves the public website, customer/organizer/admin auth, web/admin/organizer routes, shared APIs, checkout/payments, Box Office POS, organizer workforce, ticket issuance, multi-day passes, Access credentials/admission, AI features, hero management and Mobile Homepage Studio.

## Critical architecture
- Event types: online, venue, box office.
- Server-authoritative pricing/inventory/payment/finalization.
- Payments V2: fee engine, additional/POS fees, Razorpay Route eligibility and BookTKIT-managed fallback.
- `issued_tickets` are canonical attendee entitlements.
- Access engine resolves ticket/credential to issued ticket and owns gate-aware entry/exit/re-entry state.
- Organizer staff permissions and assignments are enforced server-side.

Before backend work read root `AGENTS.md`, `project instructions.md` and `docs/CANONICAL-ARCHITECTURE.md`. Use migrations for schema changes, transactions/locks for inventory/admission and isolated staging for state-changing regression.
