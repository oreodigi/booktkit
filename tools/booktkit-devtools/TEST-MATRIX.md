# BookTKIT DevTools test matrix

This matrix is derived only from the current `oreodigi/booktkit` repository.

## Web
- Public homepage, events, organizer directory, contact/about.
- Customer login, signup, Google sign-in entry, password reset.
- Organizer login, signup, dashboard protection.
- Current event modes: Online and Venue.
- Booking, checkout, ticket delivery and booking history.

## API
- Customer authentication/session behavior.
- Event discovery and booking endpoints.
- Organizer Sanctum-protected routes.
- BookTKIT Razorpay v1 order creation and verification.
- Organizer payment settings and Razorpay webhook.
- Notifications and FCM registration.

## Security/correctness regression
- Server-calculated price/fee/tax totals.
- Payment verification and idempotent completion.
- Organizer/customer ownership isolation.
- Ticket/event ownership validation.
- Inventory and last-ticket concurrency.
- Upload allowlisting/private storage.
- Password reset expiry/attempt limiting.
- QR must resolve an actually issued ticket.
- Repeated and simultaneous QR scans must be atomic.

## Flutter apps
- Customer app: API_BASE_URL and PGW_BASE_URL compatibility, auth, discovery, booking, payments, tickets, notifications.
- Organizer app: login, events, tickets, bookings, finance/payment settings, withdrawals and ownership.
- Scanner app: login, event/attendee retrieval, valid/invalid/repeated QR admission.

## Environments
Production checks are read-only. Booking creation, payment execution, event creation, refunds, withdrawals and QR admission require an approved staging/test database and gateway test mode.
