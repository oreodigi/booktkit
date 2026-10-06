# BookTKIT Scanner / Access App

Flutter venue-access client for BookTKIT.

Current backend admission supports organizer/staff sessions, authorized event/gate discovery, secure issued-ticket and credential resolution, explicit ENTRY/EXIT, re-entry policy, date-scoped pass entitlement and audited override.

New scanner work must use the unified server admission engine; do not rely on legacy manual scan-status mutation or local QR claims. Use staging for mutation tests and read `AGENTS.md` plus `docs/mobile/*`.
