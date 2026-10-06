# BookTKIT

Production event-ticketing, Box Office POS and venue-access platform for BookTKIT.

Canonical repository: `oreodigi/booktkit` (private). Read `AGENTS.md` and `project instructions.md` before changes.

## Applications
- `source/website` — Laravel website, APIs, admin and organizer operations.
- `source/customer-app` — Flutter customer app.
- `source/organizer-app` — Flutter organizer app.
- `source/scanner-app` — Flutter scanner/access app.
- `tools/booktkit-devtools` — Playwright/MCP/ChatGPT QA.
- `deploy` — GitHub-to-cPanel deployment/rollback tooling.

## Current capability baseline — 6 October 2026
Current `main` includes online/venue/box-office events; unified event forms; Box Office POS, holds, shifts and reporting; organizer team/RBAC; Payments V2 and Razorpay fallback settlement; secure issued tickets; QR/RFID credential lifecycle; gates/zones and entry/exit/re-entry admission; multi-day passes; AI credits/image generation; managed hero banners; Mobile Homepage Studio; and isolated staging QA tooling.

See `docs/CANONICAL-ARCHITECTURE.md`, `docs/BOOKTKIT-ACCESS-CREDENTIAL-SYSTEM.md`, `docs/BOX-OFFICE-DESIGN.md`, `docs/PAYMENTS-V2.md` and `docs/mobile/MOBILE-API-CONTRACT.md`.

Production credentials, customer uploads, databases and generated runtime files are not repository contents. GitHub state and production deployment state are separate; verify the deployed commit before claiming a change is live.
