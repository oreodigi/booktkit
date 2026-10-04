# Customer APK compatibility evidence

5 October 2026; build baseline and exact toolchain are recorded in [PROGRESS.md](PROGRESS.md). This is build evidence, not production compatibility certification. See [the full audit](../../project-review/FULL-AUDIT-2026-10-05.md) for broader findings.

| Area | Evidence | Status |
| --- | --- | --- |
| Android build | Debug APK compiled; aapt reports com.booktkit.customer, min SDK 24, target SDK 36, three ABIs; signature verified | Passed static artifact checks |
| Account menu | Font Awesome 11 icon types migrated; widget test renders two menu icons and checks booking-menu tap callback | Passed widget test |
| Category filter | Nullable category name guarded; full analyzer clean | Static check passed; navigation not tested on device |
| API environment | AppUrls defaults to booktkit.com/api and booktkit.com/pgw; helper URLs still include production defaults | Production defaults retained; staging not configured |
| Authentication and homepage | No authenticated runtime/device test in this build task | Unverified |
| Checkout and notifications | Earlier audit reports mandatory FCM, server checkout mismatch and permissive WebView payment detection | Unresolved; purchase testing requires staging |
| Booking → organizer → scanner | No end-to-end transaction performed | Unverified |
| Store release | Debug signing and version 1.0.0 (1) | Requires owned release signing and release validation |

The Font Awesome migration follows the [publisher's version 11 changelog](https://pub.dev/packages/font_awesome_flutter/changelog). No backend or live deployment was changed.
