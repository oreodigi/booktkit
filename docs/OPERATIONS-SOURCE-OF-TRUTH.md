# BookTKIT Operational Source of Truth

Last reconciled: 5 October 2026.

## Authority
Operational facts must be verified from the live server and current GitHub state. Historical files such as `project-review/LIVE-DEPLOYMENT.md` and `deploy/SETUP-STATUS.md` describe specific moments in time and may be stale.

## Production
- Domain: `https://booktkit.com`
- Host: `server.tejum.cloud`
- cPanel account: `booktkit`
- Application path: `/home/booktkit/public_html`
- PHP baseline observed in production: PHP 8.3.
- Framework lockfile baseline: Laravel 9.52.x.

## Git
Canonical repository: `oreodigi/booktkit`, branch `main`.
GitHub is authoritative for maintained application source. Direct production edits must be reconciled into Git.

## Deployment tooling
Private runtime locations documented by the deployment system:
- runner: `/home/booktkit/booktkit-deploy/deploy.py`
- state: `/home/booktkit/booktkit-deploy/state.json`
- log: `/home/booktkit/booktkit-deploy/deploy.log`
- failure state: `/home/booktkit/booktkit-deploy/failure.json`
- backups: `/home/booktkit/booktkit-backups/git-deploy/`
- pause marker: `/home/booktkit/booktkit-deploy/PAUSED`

Do not infer current deployment status from this document. Read the live state/log and verify the deployed commit.

## Testing
BookTKIT DevTools and E2E workflows are present under `.github/workflows` and `tools/booktkit-devtools`.
Production mutation tests are prohibited by default. Use isolated staging and disposable accounts/test-mode gateways for state-changing regression.

## Server access
Remote Desktop Commander access to `server.tejum.cloud` is authorized for BookTKIT work. Limit changes to BookTKIT resources. Never reveal secrets retrieved from the server.

## Status-doc rule
Whenever a status document conflicts with current GitHub code/commits, current server deployment state/logs, or current live behavior, verified current evidence wins.

Update status documentation when major deployment architecture or environment facts change.

## QA isolation update (2026-10-06 IST)

Staging is now `/home/booktkit/staging` with database `booktkit_stage`, protected by Basic auth and noindex. The owner approved directory isolation within the existing account. Production remains `/home/booktkit/public_html` / `booktkit_ems`. The documented private runner is now installed and scheduled as booktkit; the previously active root `git-deploy/deploy.sh` cron is retired. Staging has separate `booktkit-staging-deploy` state/log/failure/pause files. Refer to `deploy/SETUP-STATUS.md` for dated commits, backups and real test counts. Staging migrates after DB backup; production needs the explicit `--migrate` flag.
