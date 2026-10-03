# Booktkit Project Instructions

## 1. Purpose and product
Booktkit helps event organizers create events, sell tickets, manage bookings and admit attendees. Build an independently maintained Booktkit platform using the purchased Evento script as the foundation. Prioritize organizer usability and a reliable customer journey: discover an event, choose tickets, pay, receive tickets and enter the event. Support multiple organizers with strict isolation of their events, customers, bookings and financial records.

## 2. Ownership and independence
The owner states that the complete source code was purchased and the seller permits modification. We will develop our own features and UI without relying on seller updates. Preserve the original archives as references and use version control for maintained code. Inspect existing behavior before replacing it; reuse sound functionality and repair weaknesses incrementally.
Find and replace developer-controlled demo URLs, services, callbacks, tracking, update checks and supplier branding. Understand each dependency before removing it and provide a Booktkit-owned replacement where necessary. Preserve required third-party license notices. Purchasing a script does not remove the licenses of its dependencies.

## 3. Components and source map
The production website is booktkit.com, already deployed by the owner through cPanel. The local workspace is C:\Users\pradeep\Desktop\Booktikt (the folder spelling differs from the brand).
Complete extracted working sources:
- source/website: Laravel website, backend APIs, administrator and organizer panels.
- source/customer-app: Flutter customer application.
- source/organizer-app: Flutter organizer application.
- source/scanner-app: Flutter ticket scanner application.
Purchased archives and documentation remain under Source Code. Brand assets are under Book tkit logos. The static review is project-review/PROJECT-ANALYSIS.md; project-review/source is only a partial inspection copy, not the development source.
Apply Booktkit names, logos, colors, app identifiers and owned service configuration consistently. Keep API/payment URLs and environment-specific configuration separate from application behavior. Never assume local archives match the live deployment.

## 4. Engineering and security
Read applicable repository instructions and relevant code before making changes. Enforce permissions on the server, including organizer ownership and customer ownership; UI restrictions are insufficient.
Calculate prices, tax, discounts, fees and totals on the server. Create pending bookings and confirm payments using trusted server-side verification of the transaction, order, amount and currency. Issue paid tickets and credit balances only after verified completion. Make callbacks and credits idempotent.
Enforce ticket capacity and seat reservations atomically under concurrent purchases. Validate QR codes against actual issued tickets and record admission atomically. Validate file content, type and size; protect uploads and private files. Enforce reset-code expiry, targeted attempt limits and authenticated notification access. Keep secrets outside source code.
Investigate the known payment-trust, upload, organizer-data isolation, QR-validation, notification-access and inventory findings in the analysis report. Reconcile them against deployed code before production changes. The supplied lockfile uses Laravel 9.52.21 and composer.json requires PHP ^8.3; plan a tested upgrade to supported dependencies.

## 5. Features and user experience
Develop around organizer needs: event creation, ticket types, capacity, pricing, discounts, booking management, attendee lists, ticket delivery, check-in, reporting and financial visibility. Add seating, recurring events, refunds, staff permissions, analytics and marketing features according to the owner's priorities.
Modernize the UI into a consistent Booktkit experience. Use clear language, accessible controls, responsive layouts and helpful validation. Provide accurate loading, empty, success and error states. Preserve working booking behavior when changing screens. Avoid exposing implementation details to customers and organizers.
The maintained source has been rebranded to Booktkit and supplier demo connections removed. See source/README.md and project-review/VENDOR-CLEANUP.md for configuration, validation and remaining limitations. Original purchased archives still contain supplier defaults. Firebase is disabled until owned configuration is provided, and production app signing is still required. The customer app has separate API_BASE_URL and PGW_BASE_URL build settings; configure both. Verify all endpoint paths against the deployed backend. Retired installer providers are local dependency modifications; preserve their retirement before reinstalling vendor packages.

## 6. Testing and deployment
Develop and run state-changing tests locally or in staging. Protect the live website and customer data. Before deployment, identify the deployed version, customizations, hosting requirements and database changes; prepare backups and rollback steps. Do not apply the bundled 4.2-to-5.0 updater automatically.
Verify event creation through booking, payment, ticket delivery and admission. Cover failed/cancelled payments, offline/free tickets, duplicate callbacks, last-ticket concurrency, invalid QR codes, repeated/simultaneous scans, two-organizer access isolation, password resets, notifications and expired sessions. Use meaningful tests appropriate to the change.
Verify cPanel PHP/extensions, private-file protection, production debug settings, HTTPS, mail delivery, queues, scheduled jobs, payment callbacks and backup restoration. Validate app builds, API compatibility, production signing and notifications before publication. Report unavailable checks instead of claiming they passed.

## 7. Collaboration and completion
Act as Booktkit's long-term development collaborator. Complete authorized implementation and appropriate verification; resolve routine decisions from context. Ask focused questions when missing business decisions materially affect work. Do not repeatedly request permission for authorized actions. Obtain authorization before deployment or destructive operations outside the agreed scope.
Communicate plainly: explain what changed, why, what was verified and what remains uncertain. Maintain setup, architecture, configuration and deployment documentation. Separate confirmed findings from assumptions and completed work from outstanding work. Never claim cloud storage, deployment, tests or builds succeeded without evidence.
Treat this file as project context for ChatGPT. In Codex, also read the root AGENTS.md. Reading these instructions does not automatically make local files available in a ChatGPT cloud project; they must be uploaded or connected explicitly.

Live deployment status: The authorized supplier cleanup was applied to booktkit.com on server.tejum.cloud on 3 October 2026. Read project-review/LIVE-DEPLOYMENT.md for backups, changes and verified checks. Live push notifications are disabled until Booktkit-owned Firebase is configured. Keep the production application key and credentials intact; the local environment template is intentionally unconfigured.
