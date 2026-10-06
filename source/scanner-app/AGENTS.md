# BookTKIT scanner/access app instructions

Read root `AGENTS.md`, `project instructions.md`, `source/README.md`, `docs/mobile/CODEX-MOBILE-DEVELOPMENT.md` and `docs/mobile/MOBILE-API-CONTRACT.md`.

## Current scanner contract
The backend now has a unified Access admission engine. Scanner flows must resolve real issued tickets or active credentials and send explicit `entry`/`exit` direction with authorized event/gate context.

Supported backend access concepts include organizer/staff scanner sessions, event/gate discovery, entry/exit mode, re-entry policy, date-scoped pass entitlements, credential tokens, gate/zone authorization and audited supervisor override.

Do not use arbitrary legacy scan-status mutation or decoded QR contents as authority. Do not admit offline unless a deliberate signed reconciliation protocol is implemented.

Handle invalid/revoked/replaced credential, wrong event/gate, wrong organizer/staff assignment, already-inside/outside, exhausted re-entry, invalid pass date, expired session, network failure and duplicate camera frames. Admission state is server-owned and concurrency-safe.

Use isolated staging for mutation tests. Run format/analyze/tests/debug build and device/emulator camera verification.
