# BookTKIT Canonical Architecture

Last reconciled with GitHub `main`: 5 October 2026.

## Purpose
This file is the compact architecture source of truth for agents and developers. Historical audits under `project-review/` are evidence, not a replacement for current code inspection.

## Repository map
- `source/website` — Laravel 9.x / PHP 8.3 website and shared backend.
- `source/customer-app` — Flutter customer application.
- `source/organizer-app` — Flutter organizer application.
- `source/scanner-app` — Flutter ticket/admission scanner.
- `tools/booktkit-devtools` — Playwright browser/API/regression tests, diagnostics, MCP control plane and ChatGPT testing package.
- `docs/mobile` — mobile engineering instructions and backend contract baseline.
- `deploy` — cPanel deployment/rollback tooling.
- `project-review` — audits, vendor-cleanup history and historical live-deployment reports.

## Website/backend
The Laravel application owns the canonical business data and rules. Route surfaces include public/customer web routes, admin routes, organizer routes, shared API routes and scanner API routes.

Core domains include customers, organizers, events, categories, dates, tickets, variations, bookings, coupons, wishlists, finance/earnings, support, notifications and scanner admission.

Current repository additions also include Mobile Homepage Studio models/services for templates, sections, campaigns and versions.

## Payments
The repository contains a newer Razorpay orchestration path using server-side services including:
- `AuthoritativeTicketPricingService`
- `PaymentOrderService`
- `BookingFinalizationService`
- `PaymentReconciliationService`
- `PlatformFeeCalculator`
- `RazorpayRouteService`
- `RazorpayWebhookService`

The server must remain authoritative for prices, fees, currency, payment state, booking finalization and ticket issuance. Legacy gateway/payment paths coexist and must be inspected before changes.

## Authentication
The system contains distinct customer, organizer and admin flows plus Sanctum-style API guards. Socialite/Google auth and reCAPTCHA v3 are present in current development. Native mobile auth compatibility must be verified independently from browser OAuth behavior.

## Mobile applications
All three Flutter applications consume the Laravel backend. Never assume purchased mobile clients match current APIs.

Customer app: discovery, authentication, checkout, bookings/tickets, support/notifications.

Organizer app: organizer auth, event/ticket management, bookings, finance/settings and related organizer capabilities.

Scanner app: organizer/admin scanner authentication, authorized events, QR validation and admission.

Before mobile changes read `docs/mobile/CODEX-MOBILE-DEVELOPMENT.md`, `docs/mobile/MOBILE-API-CONTRACT.md` and the app's own `AGENTS.md`.

## QA system
`tools/booktkit-devtools` provides repository-level Playwright testing and the BookTKIT MCP/plugin testing layer. CI workflows include BookTKIT DevTools and E2E jobs. Production defaults to read-only testing; mutation suites require approved isolated staging/test data.

## Production
Domain: `https://booktkit.com`
Server: `server.tejum.cloud`
cPanel account: `booktkit`
Live path: `/home/booktkit/public_html`

Production is cPanel-hosted. It is not a Vercel deployment.

## Deployment
`deploy/` contains the maintained GitHub-to-cPanel deployment/rollback tooling. The deployment runner stages tracked website files, validates changes, preserves runtime/private state, records deployed commits and supports rollback. A Git commit is not proof of production deployment; verify runtime/deployment state.

## Non-canonical system
`sourcecatch-konnect/eventora` is scrapped and must never supply architecture assumptions for BookTKIT.