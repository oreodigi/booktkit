# BookTKIT Agent Entry Point

Before working on this repository, read in order:
1. `project instructions.md`
2. `docs/CANONICAL-ARCHITECTURE.md`
3. `docs/OPERATIONS-SOURCE-OF-TRUTH.md`
4. The scoped documentation for the subsystem being changed.

## Canonical scope
Repository: `oreodigi/booktkit`.

The old `sourcecatch-konnect/eventora` project is scrapped. Never restore its architecture, Supabase/Vercel assumptions, schemas or implementation decisions unless the current BookTKIT code independently contains them.

## Maintained applications
- `source/website` — Laravel website/shared backend, admin and organizer panels.
- `source/customer-app` — Flutter customer app.
- `source/organizer-app` — Flutter organizer app.
- `source/scanner-app` — Flutter scanner app.
- `tools/booktkit-devtools` — Playwright/MCP/ChatGPT QA stack.
- `deploy` — cPanel deployment and rollback tooling.

The live website is separately deployed to the BookTKIT cPanel account on `server.tejum.cloud`; a commit is not proof of deployment. Verify deployed commit and live behavior.

## Mobile development
Before changing any Flutter app, also read:
- `docs/mobile/CODEX-MOBILE-DEVELOPMENT.md`
- `docs/mobile/MOBILE-API-CONTRACT.md`
- the app's scoped `AGENTS.md`.

Reconcile backend routes/contracts before implementing mobile behavior.

## Evidence rule
Current code and verified runtime evidence outrank historical Markdown reports. Files under `project-review/` and older status documents are historical evidence and can become stale.

Never claim deployment, tests, builds or fixes succeeded without verification.