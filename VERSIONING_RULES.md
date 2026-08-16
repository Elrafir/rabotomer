# Правила версионирования и релизов Работомера

## 1. Строгий SemVer (Семантическое Версионирование)
- **Patch (X.Y.Z → X.Y.Z+1, например 2.2.0 → 2.2.1)**: Любые багфиксы, мелкие визуальные и логические исправления.
- **Minor (X.Y.Z → X.Y+1.0, например 2.2.x → 2.3.0)**: Добавление нового функционала / экранов / инструментов.
- **Major (X.Y.Z → X+1.0.0, например 2.x → 3.0.0)**: Кардинальные архитектурные переработки.
- **НИКОГДА** не перезаписывать релизную версию тем же номером при внесении правок. Всегда инкрементировать версию и `versionCode` (Build).

## 2. Где обновлять версию при релизе:
1. `application/config/app_version.php` — `$config['app_version']`, `$config['app_release_date']`, `$config['app_notes']`.
2. `/home/alexey/www/time-android/android/app/build.gradle` — `versionCode <N>`, `versionName "X.Y.Z"`.
3. `/home/alexey/www/time-android/www/index.html` — бейдж `vX.Y.Z` под индикатором сети и `currentVersion = 'X.Y.Z'`.
4. `timeline.js` / `ui.js` — шапка комментариев.

## 3. Организация папки загрузок APK (`assets/downloads/`):
- Старые версии **всегда перемещаются** в подпапку: `assets/downloads/old/`.
- В корне `assets/downloads/` публикуются ровно **два экземпляра** актуального билда:
  1. `Работомер.apk` (постоянная ссылка на свежайшую версию)
  2. `Работомер_vX.Y.Z.apk` (версионированный файл)
- Выставлять права `chmod 644` на загружаемые APK.

## 4. Процесс сборки:
```bash
# 1. Синхронизация ассетов
cd /home/alexey/www/time-android && npx cap sync android

# 2. Сборка APK через Gradle
export JAVA_HOME=/home/alexey/.jdk/jdk-17.0.20+8
export PATH=$JAVA_HOME/bin:$PATH
cd /home/alexey/www/time-android/android && ./gradlew assembleDebug

# 3. Перемещение старых версий в old и копирование новых
mkdir -p /home/alexey/www/time/assets/downloads/old
mv /home/alexey/www/time/assets/downloads/*.apk /home/alexey/www/time/assets/downloads/old/ 2>/dev/null
cp /home/alexey/www/time-android/android/app/build/outputs/apk/debug/app-debug.apk /home/alexey/www/time/assets/downloads/Работомер.apk
cp /home/alexey/www/time-android/android/app/build/outputs/apk/debug/app-debug.apk /home/alexey/www/time/assets/downloads/Работомер_vX.Y.Z.apk
chmod 644 /home/alexey/www/time/assets/downloads/*.apk
```
