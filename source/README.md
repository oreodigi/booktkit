# Booktkit working sources

The four source directories are the maintained development copies. Original purchased archives under `Source Code` remain unchanged.

- `website`: Laravel website and shared backend.
- `customer-app`: `booktkit_customer`, Android application ID `com.booktkit.customer`.
- `organizer-app`: `booktkit_organizer`, application ID `com.booktkit.organizer`.
- `scanner-app`: `booktkit_scanner`, application ID `com.booktkit.scanner`.

Names and platform identifiers have been changed consistently, including Dart imports and Android activity locations. Existing package IDs are proposed Booktkit identifiers; their availability in app stores has not been verified.

## Configuration required before running

Website `.env` has been reset to an unconfigured local template, without the supplier's database, mail, application key or cloud credentials. Configure your own database and mail settings and generate a new application key for this local installation. Do not replace the existing production application key: this source cleanup is not a deployment.

`website/public/config.php` reads payment secrets from environment variables. Supply Booktkit-owned credentials through server configuration; PHP `getenv` must be able to read them. A Laravel `.env` file alone does not guarantee availability to these standalone payment scripts. Their production behavior still requires verification.

Mobile defaults now use https://booktkit.com. Use staging overrides for testing; do not perform development transactions against production. Customer URLs accept `API_BASE_URL` and `PGW_BASE_URL` build definitions. Organizer/scanner currently configure their base URL in their respective URL/client source files.

Customer Firebase is disabled by default. To enable owned Android Firebase, provide `BOOKTKIT_FIREBASE_ENABLED=true`, `FIREBASE_API_KEY`, `FIREBASE_APP_ID`, `FIREBASE_SENDER_ID`, `FIREBASE_PROJECT_ID` and optional `FIREBASE_STORAGE_BUCKET` through Dart build definitions. Add an owned matching `android/app/google-services.json` if native Firebase configuration is needed. Never restore the supplier file. Apple/web Firebase setup remains separate work.

## Retired supplier installation services

Web installer/update providers are deliberately no-ops in the bundled dependency source. Supplier email collector/controller/route/config and nested installer payloads have been removed. Public installer assets/demo SQL and application installer views have been removed. Use Booktkit-controlled setup and migrations going forward.

Composer dependency/lock/autoload metadata is preserved to keep the bundled dependency graph consistent; supplier names can remain there as provenance. Other third-party author/license notices are preserved. Do not refresh vendor packages without first retaining these installer-provider changes or replacing the retired packages in a controlled dependency migration. Composer can restore upstream package contents when reinstalling them.

## Validation and release limits

Static validation passed for local Dart package imports, Android XML manifests, web manifests and Postman collection JSON. No PHP/Flutter build or end-to-end test ran because those runtimes were not found on this machine's command path. App signing still needs production configuration. The backend issues in `../project-review/PROJECT-ANALYSIS.md` remain a separate priority.

The live cPanel application/database and cloud project were not modified.
