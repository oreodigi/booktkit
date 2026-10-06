# BookTKIT Operational Source of Truth

Last documentation reconciliation: 6 October 2026.

## Authority
1. Current canonical `oreodigi/booktkit` code/branch.
2. Verified deployment/runtime state on `server.tejum.cloud`.
3. Current canonical architecture/operations docs.
4. Project conversation.
5. Historical reports.

A Git commit is not proof it is deployed. Historical `project-review/*` and dated setup files must never override current runtime evidence.

## Production
- Domain: `https://booktkit.com`
- Host: `server.tejum.cloud`
- cPanel account: `booktkit`
- Application path: `/home/booktkit/public_html`
- Runtime baseline: PHP 8.3 / Laravel 9.x.

## Staging
The current documented isolated staging design uses `/home/booktkit/staging`, separate staging database, Basic auth/noindex and BookTKIT staging deployment state. Verify live runner/state before relying on these facts.

State-changing regression belongs on isolated staging. Production tests are read-only by default.

## Deployment
BookTKIT has private deployment/rollback tooling and records deployed commit/state/logs. Before promotion verify branch/head, backup/migration requirements, deployment marker and QA. After promotion verify deployed commit plus live smoke behavior. Direct production fixes must be reconciled back into Git.

## Current high-risk operational domains
Recent main changes affect event forms, Box Office/POS, staff RBAC, Payments V2, pass entitlements, issued tickets and Access credentials/admission. Deploy these with migration awareness and cross-domain regression, especially payment finalization and scanner/access flows.

## Security
Never expose environment secrets, database/gateway credentials, tokens or signing material. Keep changes isolated to BookTKIT resources.

## Status rule
When a status file conflicts with current code, live deployment state/logs or verified behavior, verified current evidence wins. Update canonical docs after major architecture changes; preserve historical reports as dated evidence rather than rewriting history.
