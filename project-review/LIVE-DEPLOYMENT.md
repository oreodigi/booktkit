# Booktkit live supplier cleanup — 3 October 2026

Applied through Remote Desktop Commander to server.tejum.cloud, cPanel account booktkit, document root /home/booktkit/public_html. The live website is https://booktkit.com. WHM identifies PHP 8.3; deployed script version is 5.0 and framework lockfile version is Laravel 9.52.21.

## Backup and rollback
Before edits, both the entire website and its database were backed up:
`/home/booktkit/booktkit-backups/20261003T145821Z-vendor-cleanup`

The protected folder contains website.tar.gz (287,411,214 bytes), database.sql (1,294,768 bytes), original changed files, quarantined supplier files, old database settings, database-branding-changes.json, CHANGE-MANIFEST.json with resulting file hashes, HTTP-VERIFICATION.json, and rollback.py.

Rollback procedure: review the change manifest and any changes made since this deployment, then run rollback.py on the server as root. It restores the targeted files/settings, rather than overwriting the whole database. Its syntax was verified; it was not executed. Full backups remain available.

## Live changes
- Applied Booktkit application naming and changed APP_URL from localhost to https://booktkit.com, with APP_ENV=production. Existing application key, database connection, mail settings, orders, tickets, and payment secrets were preserved.
- Corrected the standalone payment script base URL from evento.test to Booktkit.
- Disabled supplier web installer/updater providers and quarantined their routes, controllers, email-collection configuration, views, nested dependencies and installation payload.
- Moved the installation ZIP and supplier API examples out of the public application.
- Removed the configured supplier Firebase service-account file from public storage and cleared its backend reference. Supplier-controlled push notifications are disabled until owned Firebase configuration is supplied.
- Reused existing Booktkit website logo/favicon assets for mobile/scanner API branding and updated local website logo fallbacks.
- Removed the organizer link's supplier tooltip.
- Updated 17 database fields containing supplier URLs/product branding across footer links, popups, advertisement links, legacy calls to action, feature text and page content. The About link uses the verified /about-us route.
- Added explicit 404 rules for both public URL variants of the retired service-account file.
- Refreshed configuration, route and compiled-view caches.

## Verification
Six changed PHP/Blade files passed PHP syntax checks before replacement. Thirteen public pages/API/assets returned HTTP 200, including homepage, event/organizer lists, customer/organizer login, About, contact, policy pages, both mobile configuration APIs and new mobile logo/icon assets. The 11 checked HTML/JSON responses contained zero matches for the supplier names/domains screened.

Six retired paths returned HTTP 404: add-installer, install, update, both credential-file URL variants, and installable.zip. The API logo URLs point to Booktkit.

No paid purchase, refund, withdrawal, email delivery or live QR admission test was performed. These checks establish availability and targeted cleanup, not full booking/payment correctness.

## Remaining items
Configure Booktkit-owned Firebase to resume push notifications. The previously documented booking/payment, upload, data-isolation and stock issues require separate fixes.

Before these changes, artisan route:list already failed because App\\Http\\Controllers\\MyFatoorahController does not exist. This remains an existing route/controller issue and was not introduced by the cleanup.

Composer/dependency provenance and third-party attribution remain intentionally intact. Retired installer providers are local modifications to bundled dependencies; preserve their retirement before reinstalling vendor packages. Local mobile source changes are separate from this website deployment.

Historical local reports that say no production changes were made describe their earlier review only; this report records the subsequent authorized deployment.

