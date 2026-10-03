# Booktkit supplier cleanup

Applied to the local maintained `source` directories on 3 October 2026.

## Completed

- Replaced supplier demo API/payment endpoints with Booktkit defaults.
- Renamed Flutter packages, Dart imports/main app class, Android/iOS/desktop identifiers and visible application names.
- Relocated Android activity files to match new package declarations.
- Replaced supplier credits and local logo fallbacks with Booktkit branding.
- Generated 100 Android/iOS/macOS/web icon assets from the supplied Booktkit Icon.png, preserving each target's dimensions.
- Removed supplier Firebase Android configuration and service-account JSON; customer Firebase now requires explicit owned configuration and is disabled by default. Initialization/notification paths check for configuration or initialized Firebase.
- Disabled supplier installer and web updater service providers; removed the supplier's installer payload, email collector routes/controllers/configuration, public installer and demo database SQL, and installer views.
- Removed bundled sessions, cached views/package manifests, application logs and payment-script error logs.
- Reset the website environment to an unconfigured local template; standalone payment settings now load secrets from environment variables.
- Rebranded and sanitized Postman API examples, including removal of example authorization/cookie headers.
- Added website ignore rules for local secrets and generated/dependency files.

## Intentionally retained

Purchased archives, original documentation and historical review snapshots are references, not maintained runtime source. Composer metadata and third-party author/license notices retain dependency provenance. Framework/payment-provider libraries remain required software dependencies. The installed supplier provider stubs retain their class namespace for autoload compatibility but expose no installation/update services.

The WebTend attribution in three template CSS/JS file headers is third-party attribution and was preserved. It is a source comment, not a runtime connection or visible Booktkit credit.

## Validation

All three Android manifests and web manifests parse. Postman JSON parses. Every local Dart package reference resolves after renaming (zero missing paths). Scanning application source outside dependency records/third-party attribution found no supplier demo endpoints, supplier Firebase project, old Evento Dart package references or old Evento application IDs.

PHP, Composer, Dart and Flutter were unavailable through the command path. Runtime behavior and build success are unverified. Database-driven logos/contact details on the live site were not changed; that requires inspecting the deployed database/settings. Additional photographic/demo content may remain as ordinary sample content, not supplier service connections.

See `../source/README.md` for required configuration and dependency-maintenance caveats. No production or cloud changes were made.
