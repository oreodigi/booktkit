# BookTKIT organizer app instructions

Read root `AGENTS.md`, `project instructions.md`, `source/README.md`, `docs/mobile/CODEX-MOBILE-DEVELOPMENT.md` and `docs/mobile/MOBILE-API-CONTRACT.md`.

## Current requirements
Use organizer-scoped Laravel APIs and current backend models. Current web backend includes online/venue/box-office events, passes, POS, workforce, Payments V2 and Access credentials; do not assume equivalent organizer-app screens/endpoints exist until verified.

Organizer finance must display server-calculated fees, settlement preference/effective mode and Razorpay eligibility/fallback. Never reproduce fee calculations client-side.

Event management must follow current event-type validation and shared domain behavior. Access/credential operations require granular permissions and event/gate scope. Staff identities are not organizer superusers.

Centralize staging URLs. Test two-organizer isolation, expired auth, validation, customer booking visibility and authorized scanner/access behavior. Run format/analyze/tests/debug build.
