/**
 * Mobile Heartbeat, Connectivity & Auto-Update Manager
 * Отслеживает доступность сервера, показывает 30-сек плашку подключения,
 * автоматически обнаруживает альтернативный адрес (Хотспот) и предлагает переход.
 */
(function() {
    let firstFailureTime = null;
    const TIMEOUT_OFFLINE_MS = 30000; // 30 секунд до вывода главного окна ошибки
    const CHECK_INTERVAL = 7000;      // 7 секунд период проверки
    let isChecking = false;
    let offlineModalElement = null;
    let updateModalElement = null;
    let reconnectBannerElement = null;
    let latestServerVersionData = null;
    let detectedAlternativeUrl = null;

    // Функция мгновенного возврата на локальный лаунчер без сетевых запросов
    function goToSetupScreen() {
        try { localStorage.removeItem('timeTrackerServerUrl'); } catch(e) {}
        
        let localLauncher = null;
        try { localLauncher = localStorage.getItem('timeTrackerLocalLauncher'); } catch(e) {}

        if (localLauncher) {
            window.location.href = localLauncher.split('?')[0] + '?reset=1';
            return;
        }

        // Запасные варианты для WebView / Capacitor / Cordova
        if (window.location.protocol === 'file:') {
            window.location.href = 'file:///android_asset/public/index.html?reset=1';
        } else {
            window.location.href = window.location.origin + '/MobileApp/reset_setup';
        }
    }
    window.goToSetupScreen = goToSetupScreen;

    // --- 1. ПЛАШКА "ПОПЫТКА ПОДКЛЮЧЕНИЯ" (До 30 секунд) ---
    function getReconnectBanner() {
        if (!reconnectBannerElement) {
            reconnectBannerElement = document.createElement('div');
            reconnectBannerElement.id = 'mobileReconnectBanner';
            reconnectBannerElement.style.cssText = `
                position: fixed;
                top: 12px;
                left: 50%;
                transform: translateX(-50%);
                background: rgba(30, 41, 59, 0.95);
                border: 1px solid rgba(234, 179, 8, 0.5);
                backdrop-filter: blur(8px);
                color: #fef08a;
                padding: 8px 18px;
                border-radius: 9999px;
                font-size: 13px;
                font-weight: 600;
                z-index: 999990;
                display: none;
                align-items: center;
                gap: 8px;
                box-shadow: 0 10px 15px -3px rgba(0,0,0,0.4);
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            `;
            reconnectBannerElement.innerHTML = `
                <span style="display:inline-block; width:12px; height:12px; border:2px solid #fef08a; border-top-color:transparent; border-radius:50%; animation:hb-spin 0.8s linear infinite;"></span>
                <span id="reconnectBannerText">Попытка подключения к серверу...</span>
            `;
            document.body.appendChild(reconnectBannerElement);

            if (!document.getElementById('hb-spin-style')) {
                const style = document.createElement('style');
                style.id = 'hb-spin-style';
                style.innerHTML = `@keyframes hb-spin { to { transform: rotate(360deg); } }`;
                document.head.appendChild(style);
            }
        }
        return reconnectBannerElement;
    }

    function showReconnectBanner(elapsedSec) {
        const banner = getReconnectBanner();
        const textEl = document.getElementById('reconnectBannerText');
        if (textEl) textEl.innerText = `Попытка подключения к серверу (${elapsedSec} сек)...`;
        banner.style.display = 'flex';
    }

    function hideReconnectBanner() {
        if (reconnectBannerElement) reconnectBannerElement.style.display = 'none';
    }

    // --- 2. ПРОВЕРКА АЛЬТЕРНАТИВНОГО СЕРВЕРА И АВТО-СКАНИРОВАНИЕ SUBNET ---
    async function scanSubnetForServer(subnetPrefix) {
        const promises = [];
        let foundUrl = null;

        for (let i = 1; i <= 254; i++) {
            const targetUrl = `http://${subnetPrefix}${i}:7880`;
            if (window.location.origin.includes(targetUrl.replace('http://', ''))) continue;

            promises.push((async () => {
                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 800);
                    const res = await fetch(targetUrl + '/index.php/MobileApp/version?t=' + Date.now(), {
                        method: 'GET',
                        signal: controller.signal,
                        cache: 'no-store'
                    });
                    clearTimeout(timeoutId);
                    if (res.ok || res.status < 400) {
                        foundUrl = targetUrl;
                    }
                } catch(e) {}
            })());
        }

        await Promise.all(promises);
        return foundUrl;
    }

    async function checkAlternativeServers() {
        const currentOrigin = window.location.origin;
        const candidates = [
            'http://10.177.61.1:7880',
            'http://10.177.61.62:7880',
            'http://10.129.176.1:7880',
            'http://192.168.100.2:7880'
        ].filter(u => !currentOrigin.includes(u.replace('http://', '')));

        // 1. Быстрый пинг предустановленных серверов
        for (const targetUrl of candidates) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 1200);
                const res = await fetch(targetUrl + '/index.php/MobileApp/version?t=' + Date.now(), {
                    method: 'GET',
                    signal: controller.signal,
                    cache: 'no-store'
                });
                clearTimeout(timeoutId);
                if (res.ok || res.status < 400) {
                    detectedAlternativeUrl = targetUrl;
                    updateOfflineModalWithAlternative(targetUrl);
                    return targetUrl;
                }
            } catch(e) {}
        }

        // 2. Динамический WebRTC авто-сканер подсети
        let localIp = null;
        try {
            localIp = await new Promise((resolve) => {
                const pc = new RTCPeerConnection({ iceServers: [] });
                pc.createDataChannel('');
                pc.createOffer().then(offer => pc.setLocalDescription(offer)).catch(() => resolve(null));
                pc.onicecandidate = (ice) => {
                    if (!ice || !ice.candidate || !ice.candidate.candidate) return;
                    const match = ice.candidate.candidate.match(/(?:[0-9]{1,3}\.){3}[0-9]{1,3}/);
                    if (match) {
                        const ip = match[0];
                        if (ip !== '127.0.0.1' && !ip.startsWith('169.254')) {
                            pc.close();
                            resolve(ip);
                        }
                    }
                };
                setTimeout(() => resolve(null), 800);
            });
        } catch(e) {}

        if (localIp) {
            const parts = localIp.split('.');
            const prefix = `${parts[0]}.${parts[1]}.${parts[2]}.`;
            const scannedUrl = await scanSubnetForServer(prefix);
            if (scannedUrl) {
                detectedAlternativeUrl = scannedUrl;
                updateOfflineModalWithAlternative(scannedUrl);
                return scannedUrl;
            }
        }

        return null;
    }

    function updateOfflineModalWithAlternative(targetUrl) {
        const altContainer = document.getElementById('altServerContainer');
        if (!altContainer) return;
        
        let label = 'Найденный сервер';
        if (targetUrl.includes('10.177.61.62') || targetUrl.includes('10.177.61.1')) label = `Хотспот (${targetUrl.replace('http://', '')})`;
        else if (targetUrl.includes('10.129.176.1')) label = 'Хотспот Алиас (10.129.176.1)';
        else if (targetUrl.includes('192.168.100.2')) label = 'Локальная сеть (192.168.100.2)';
        else label = targetUrl.replace('http://', '');

        altContainer.innerHTML = `
            <button id="btnSwitchAltServer" style="
                width: 100%;
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                border: none;
                padding: 13px;
                border-radius: 12px;
                font-size: 15px;
                font-weight: 700;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
                margin-bottom: 4px;
            ">⚡ Переключиться на ${label}</button>
        `;
        altContainer.style.display = 'block';

        document.getElementById('btnSwitchAltServer').addEventListener('click', function() {
            try { localStorage.setItem('timeTrackerServerUrl', targetUrl); } catch(e) {}
            window.location.href = targetUrl;
        });
    }

    // --- 3. МОДАЛЬНОЕ ОКНО "ПОТЕРЯ СВЯЗИ (> 30 СЕКУНД)" ---
    function createOfflineModal() {
        if (offlineModalElement) return;

        offlineModalElement = document.createElement('div');
        offlineModalElement.id = 'mobileOfflineModal';
        offlineModalElement.style.cssText = `
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            z-index: 999999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #f8fafc;
        `;

        offlineModalElement.innerHTML = `
            <div style="
                background: #1e293b;
                border: 1px solid #334155;
                border-radius: 20px;
                padding: 26px;
                max-width: 400px;
                width: 100%;
                text-align: center;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            ">
                <div style="font-size: 44px; margin-bottom: 12px;">⚠️</div>
                <h3 style="margin: 0 0 8px 0; font-size: 20px; font-weight: 700; color: #f8fafc;">Связь с сервером не установлена</h3>
                <p style="margin: 0 0 18px 0; font-size: 14px; color: #94a3b8; line-height: 1.5;">
                    Не удалось подключиться к серверу в течение 30 секунд.<br>
                    <small style="font-size: 12px; color: #64748b;" id="offlineServerOrigin"></small>
                </p>
                
                <div id="altServerContainer" style="display:none; margin-bottom: 10px;"></div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button id="btnRetryHeartbeat" style="
                        width: 100%;
                        background: linear-gradient(135deg, #2563eb, #1d4ed8);
                        color: white;
                        border: none;
                        padding: 13px;
                        border-radius: 12px;
                        font-size: 15px;
                        font-weight: 600;
                        cursor: pointer;
                        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
                    ">🔄 Проверить ещё раз</button>
                    
                    <button id="btnResetServerUrl" style="
                        width: 100%;
                        background-color: #334155;
                        color: #cbd5e1;
                        border: none;
                        padding: 13px;
                        border-radius: 12px;
                        font-size: 14px;
                        font-weight: 600;
                        cursor: pointer;
                    ">⚙️ На страницу выбора сервера</button>
                </div>
            </div>
        `;

        document.body.appendChild(offlineModalElement);

        document.getElementById('btnRetryHeartbeat').addEventListener('click', function() {
            const btn = this;
            btn.innerText = '⏳ Проверка...';
            btn.disabled = true;
            checkServerHealth(true).then(isOk => {
                btn.innerText = '🔄 Проверить ещё раз';
                btn.disabled = false;
            });
        });

        document.getElementById('btnResetServerUrl').addEventListener('click', function() {
            goToSetupScreen();
        });
    }

    function showOfflineModal() {
        createOfflineModal();
        hideReconnectBanner();
        const originEl = document.getElementById('offlineServerOrigin');
        if (originEl) originEl.innerText = window.location.origin;
        if (offlineModalElement) offlineModalElement.style.display = 'flex';
        checkAlternativeServers();
    }

    function hideOfflineModal() {
        if (offlineModalElement) offlineModalElement.style.display = 'none';
        hideReconnectBanner();
        firstFailureTime = null;
    }

    // --- 4. ОБНОВЛЕНИЕ ПРИЛОЖЕНИЯ ---
    function getInstalledAppVersion() {
        let code = 0;
        let name = '';
        try {
            code = parseInt(localStorage.getItem('installedApkVersionCode')) || 0;
            name = localStorage.getItem('installedApkVersionName') || '';
        } catch(e) {}

        if (!code) code = window.CURRENT_APP_VERSION_CODE || 9;
        if (!name) name = window.CURRENT_APP_VERSION || '1.0.8';

        return { code, name };
    }

    function renderHeaderUpdateControls(serverData) {
        const container = document.getElementById('appUpdateHeaderContainer');
        if (!container) return;

        const installed = getInstalledAppVersion();
        const serverCode = serverData ? (serverData.versionCode || 0) : 0;

        if (serverData && serverCode > installed.code) {
            container.innerHTML = `
                <button onclick="window.showAppUpdateModal()" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-xl shadow-lg flex items-center gap-1.5 transition-all animate-pulse" title="Доступна новая версия v${serverData.version} (у вас v${installed.name})">
                    <span class="text-base leading-none">🚀</span>
                    <span class="nav-label">Скачать v${serverData.version}</span>
                </button>
            `;
        } else {
            container.innerHTML = `
                <button onclick="window.checkAppUpdateManual()" class="text-xs bg-white/10 hover:bg-white/20 text-white font-medium px-3 py-1.5 rounded-xl transition-colors flex items-center gap-1.5 opacity-80 hover:opacity-100" title="Проверить обновления">
                    <span class="text-base leading-none">🔄</span>
                    <span class="nav-label">Проверить обновления</span>
                </button>
            `;
        }
    }

    function createUpdateModal() {
        if (updateModalElement) return;

        updateModalElement = document.createElement('div');
        updateModalElement.id = 'appUpdateModal';
        updateModalElement.style.cssText = `
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            z-index: 999998;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #f8fafc;
        `;

        updateModalElement.innerHTML = `
            <div style="
                background: #1e293b;
                border: 1px solid #334155;
                border-radius: 20px;
                padding: 26px;
                max-width: 420px;
                width: 100%;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            ">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                    <div style="font-size: 32px;">🚀</div>
                    <div>
                        <h3 style="margin:0; font-size:18px; font-weight:700; color:#f8fafc;">Доступно обновление!</h3>
                        <div style="font-size:13px; color:#10b981; font-weight:600;" id="updateVersionTitle">Работомер</div>
                    </div>
                </div>

                <div style="background:#0f172a; border-radius:12px; padding:14px; margin-bottom:20px; max-height:160px; overflow-y:auto; font-size:13px; color:#cbd5e1; border:1px solid #334155;" id="updateReleaseNotes">
                    Загрузка информации об изменениях...
                </div>

                <div style="display:flex; flex-direction:column; gap:10px;">
                    <button id="btnDownloadUpdateApk" onclick="window.triggerApkDownload(event)" style="
                        width: 100%;
                        background: linear-gradient(135deg, #10b981, #059669);
                        color: white;
                        border: none;
                        padding: 13px;
                        border-radius: 12px;
                        font-size: 15px;
                        font-weight: 700;
                        cursor: pointer;
                        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
                    ">⬇️ Скачать и обновить APK</button>

                    <button onclick="window.hideAppUpdateModal()" style="
                        width: 100%;
                        background: transparent;
                        color: #94a3b8;
                        border: 1px solid #334155;
                        padding: 10px;
                        border-radius: 12px;
                        font-size: 13px;
                        font-weight: 600;
                        cursor: pointer;
                    ">Позже</button>
                </div>
            </div>
        `;

        document.body.appendChild(updateModalElement);
    }

    window.triggerApkDownload = function(e) {
        if (e && e.preventDefault) e.preventDefault();
        const apkUrl = (latestServerVersionData && latestServerVersionData.downloadUrls && latestServerVersionData.downloadUrls.android) 
            ? latestServerVersionData.downloadUrls.android 
            : window.location.origin + '/index.php/MobileApp/download/android';

        if (typeof showToast === 'function') {
            showToast('📥 Открытие скачивания APK...', 'info');
        }

        // 1. В Capacitor Android открываем через системный интент браузера/загрузчика
        if (window.Capacitor && window.Capacitor.Plugins && window.Capacitor.Plugins.App && typeof window.Capacitor.Plugins.App.openUrl === 'function') {
            window.Capacitor.Plugins.App.openUrl({ url: apkUrl }).catch(function(err) {
                console.warn('Capacitor openUrl fallback:', err);
                window.location.href = apkUrl;
            });
            return;
        }

        // 2. Обычный переход для браузера / WebView с DownloadListener
        try {
            window.location.href = apkUrl;
        } catch(err) {
            window.open(apkUrl, '_system');
        }
    };

    window.showAppUpdateModal = function() {
        if (!latestServerVersionData) return;
        createUpdateModal();
        
        document.getElementById('updateVersionTitle').innerText = `Работомер v${latestServerVersionData.version}`;
        document.getElementById('updateReleaseNotes').innerText = latestServerVersionData.releaseNotes || 'Улучшения производительности и исправления ошибок.';
        
        updateModalElement.style.display = 'flex';
    };

    window.hideAppUpdateModal = function() {
        if (updateModalElement) updateModalElement.style.display = 'none';
    };

    window.checkAppUpdateManual = async function() {
        if (typeof showToast === 'function') {
            showToast('🔍 Проверка обновлений...', 'info');
        }
        const isOk = await checkServerHealth(true);
        if (isOk) {
            const installed = getInstalledAppVersion();
            if (latestServerVersionData && latestServerVersionData.versionCode > installed.code) {
                window.showAppUpdateModal();
            } else {
                alert(`✅ У вас установлена актуальная версия приложения: Работомер v${installed.name}`);
            }
        }
    };

    // --- 5. ОСНОВНОЙ ЦИКЛ ПРОВЕРКИ (HEARTBEAT) ---
    async function checkServerHealth(force = false) {
        if (isChecking && !force) return true;
        isChecking = true;

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 4000);

        try {
            const pingUrl = window.location.origin + '/index.php/MobileApp/version?t=' + Date.now();
            const response = await fetch(pingUrl, {
                method: 'GET',
                signal: controller.signal,
                cache: 'no-store'
            });
            clearTimeout(timeoutId);
            isChecking = false;

            if (response.ok || response.status < 400) {
                const data = await response.json();
                latestServerVersionData = data;
                
                hideOfflineModal();
                renderHeaderUpdateControls(data);
                return true;
            } else {
                throw new Error(`HTTP Status ${response.status}`);
            }
        } catch (e) {
            clearTimeout(timeoutId);
            isChecking = false;

            if (!firstFailureTime) {
                firstFailureTime = Date.now();
            }

            const elapsed = Date.now() - firstFailureTime;
            const elapsedSec = Math.floor(elapsed / 1000);

            if (elapsed >= TIMEOUT_OFFLINE_MS) {
                showOfflineModal();
            } else {
                showReconnectBanner(elapsedSec);
            }
            return false;
        }
    }

    // Запуск цикла
    window.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => checkServerHealth(), 2000);
        setInterval(() => checkServerHealth(), CHECK_INTERVAL);
    });

    window.addEventListener('offline', () => {
        firstFailureTime = Date.now() - TIMEOUT_OFFLINE_MS;
        showOfflineModal();
    });

    window.addEventListener('online', () => {
        checkServerHealth(true);
    });
})();
