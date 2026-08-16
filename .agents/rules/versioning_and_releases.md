---
description: Semantic Versioning and APK Release Rules for Rabotomer
globs: ["**/*"]
alwaysApply: true
---

# Semantic Versioning and APK Release Rules

1. **Strict Semantic Versioning**:
   - Patch fixes must increment the patch number (`X.Y.Z` -> `X.Y.Z+1`, e.g. `2.2.0` -> `2.2.1`).
   - Minor features increment the minor number (`X.Y.0` -> `X.Y+1.0`, e.g. `2.2.x` -> `2.3.0`).
   - Major redesigns increment the major number (`3.0.0`).
   - NEVER overwrite a released version without bumping the version number and `versionCode` (Build).

2. **Files to Update on Release**:
   - `application/config/app_version.php` (`$config['app_version']`, `$config['app_release_date']`, `$config['app_notes']`)
   - `time-android/android/app/build.gradle` (`versionCode`, `versionName`)
   - `time-android/www/index.html` (badge and JS config)

3. **APK Downloads Structure (`assets/downloads/`)**:
   - All older APKs MUST be moved into `assets/downloads/old/`.
   - The root `assets/downloads/` must contain exactly two APK files:
     1. `Работомер.apk` (latest pointer)
     2. `Работомер_vX.Y.Z.apk` (explicit version)
   - Ensure permissions `chmod 644` on APK files.
