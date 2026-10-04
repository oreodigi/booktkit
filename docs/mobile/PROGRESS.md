# Mobile build progress

## Customer debug APK — 5 October 2026

Baseline: `761f7de9ec880277106aff99f01eae6e0096ee17` on `feature/full-audit-20261005`. Fetched origin; branch had no incoming commits. Pull skipped because the working tree contained existing edits, which were preserved.

Toolchain: Flutter 3.47.6, Dart 3.13.5, Temurin JDK 21.0.12.1+1, Gradle 8.14, Android Gradle Plugin 8.11.1, Kotlin 2.2.20, Android SDK 36, NDK 28.2.13676358. Set JAVA_HOME for the build process to the cached JDK 21; the installed Android Studio Java 25 was incompatible with the original Gradle wrapper.

Build fixes: meet Flutter's minimum Gradle/AGP/Kotlin versions, update font_awesome_flutter to 11.0.0 and migrate account icon types, restore a null-safe category filter expression. The SDK downloader's native library was blocked by Windows Application Control; standard Google command-line tools and the official NDK archive were used without changing security policy. The NDK archive matched Google's published SHA-1 checksum.

Actual checks:

- `flutter pub get`: passed; only font_awesome_flutter changed relative to the existing local lockfile.
- `dart format` on changed Dart files: passed.
- `flutter analyze`: no issues.
- `flutter test`: two tests passed; one existing placeholder and one account menu rendering/tap regression test. This is limited coverage.
- `flutter build apk --debug --no-pub`: passed, assembleDebug 386.4 seconds after dependency setup.
- `aapt dump badging`: com.booktkit.customer, version 1.0.0 (1), min SDK 24, target SDK 36; ARM32, ARM64 and x86_64.
- `apksigner verify --verbose`: passed, one signer, APK Signature Scheme v2.

Artifact: `source/customer-app/build/app/outputs/flutter-apk/app-debug.apk`, 211,001,609 bytes. SHA-256: `A42BAD181C9EFF4226A76B815AF9E9ABBD820D1D0388A24963311D15D13BDC06`. Binary is a local ignored build output, not committed.

Built using the working tree, including pre-existing customer analyzer, Gradle migration flags and Flutter SDK lockfile adjustments. Only this task's relevant changes are committed. Default environment: `https://booktkit.com/api` and `https://booktkit.com/pgw`; no custom Dart defines. No device/emulator run, staging transaction, release signing or deployment was performed. Dependency deprecation and future toolchain upgrade warnings remain.
