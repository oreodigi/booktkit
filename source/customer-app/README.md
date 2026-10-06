# BookTKIT Customer App

Flutter customer client for BookTKIT. It shares the Laravel backend with web, organizer and scanner clients.

Current integration priorities: customer auth, event discovery, Mobile Homepage configuration, server-authoritative checkout/free booking, dates/variations/pass products, bookings, real issued tickets and credential-collection state when exposed by API.

Do not infer payment success locally or reproduce fee calculations. Use staging configuration for development and read `AGENTS.md` plus `docs/mobile/*` before changes.
