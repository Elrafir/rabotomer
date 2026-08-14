/**
 * Mobile Heartbeat, Connectivity & Auto-Update Manager
 * Отслеживает доступность сервера, показывает 30-сек плашку подключения
 * и проверяет/предлагает обновления приложения.
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

    // --- 2. МОДАЛЬНОЕ ОКНО "ПОТЕРЯ СВЯЗИ (> 30 СЕКУНД)" ---
    function createOfflineModal() {
        if (offlineModalElement) return;

        offlineModalElement = document.createElement('div');
        offlineModalElement.id = 'mobileOfflineModal';
        offlineModalElement.style.cssText = `
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(15, 23, 42, 0.94);
            backdrop-filter: blur(10px);
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
                <p style="margin: 0 0 20px 0; font-size: 14px; color: #94a3b8; line-height: 1.5;">
                    Не удалось подключиться к серверу в течение 30 секунд.<br>
                    <small style="font-size: 12px; color: #64748b;" id="offlineServerOrigin"></small>
                </p>
                
                <div style="display: flex; flex-direction: column; gap: 12px;">
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
            try { localStorage.removeItem('timeTrackerServerUrl'); } catch(e) {}
            window.location.href = window.location.origin + '/MobileApp/reset_setup';
        });
    }

    function showOfflineModal() {
        createOfflineModal();
        hideReconnectBanner();
        const originEl = document.getElementById('offlineServerOrigin');
        if (originEl) originEl.innerText = window.location.origin;
        if (offlineModalElement) offlineModalElement.style.display = 'flex';
    }

    function hideOfflineModal() {
        if (offlineModalElement) offlineModalElement.style.display = 'none';
        hideReconnectBanner();
        firstFailureTime = null;
    }

    // --- 3. ОБНОВЛЕНИЕ ПРИЛОЖЕНИЯ ---
    function renderHeaderUpdateControls(serverData) {
        const container = document.getElementById('appUpdateHeaderContainer');
        if (!container) return;

        const currentCode = window.CURRENT_APP_VERSION_CODE || 8;
        const serverCode = serverData ? (serverData.versionCode || 0) : 0;

        if (serverData && serverCode > currentCode) {
            // Доступно обновление!
            container.innerHTML = `
                <button onclick="window.showAppUpdateModal()" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-xl shadow-lg flex items-center gap-1.5 transition-all animate-pulse" title="Доступна новая версия v${serverData.version}">
                    <span>🚀</span>
                    <span>Скачать v${serverData.version}</span>
                </button>
            `;
        } else {
            // Приложение в актуальном состоянии
            container.innerHTML = `
                <button onclick="window.checkAppUpdateManual()" class="text-xs bg-white/10 hover:bg-white/20 text-white font-medium px-3 py-1.5 rounded-xl transition-colors flex items-center gap-1.5 opacity-80 hover:opacity-100" title="Проверить обновления">
                    <span>🔄</span>
                    <span class="hidden sm:inline">Проверить обновления</span>
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
                        <div style="font-size:13px; color:#10b981; font-weight:600;" id="updateVersionTitle">Работомер v1.0.8</div>
                    </div>
                </div>

                <div style="background:#0f172a; border-radius:12px; padding:14px; margin-bottom:20px; max-height:160px; overflow-y:auto; font-size:13px; color:#cbd5e1; border:1px solid #334155;" id="updateReleaseNotes">
                    Загрузка информации об изменениях...
                </div>

                <div style="display:flex; flex-direction:column; gap:10px;">
                    <a id="btnDownloadUpdateApk" href="#" download target="_blank" style="
                        display: block;
                        text-align: center;
                        background: linear-gradient(135deg, #10b981, #059669);
                        color: white;
                        text-decoration: none;
                        padding: 13px;
                        border-radius: 12px;
                        font-size: 15px;
                        font-weight: 700;
                        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
                    ">⬇️ Скачать и обновить APK</a>

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

    window.showAppUpdateModal = function() {
        if (!latestServerVersionData) return;
        createUpdateModal();
        
        document.getElementById('updateVersionTitle').innerText = `Работомер v${latestServerVersionData.version}`;
        document.getElementById('updateReleaseNotes').innerText = latestServerVersionData.releaseNotes || 'Улучшения производительности и исправления ошибок.';
        
        const apkUrl = (latestServerVersionData.downloadUrls && latestServerVersionData.downloadUrls.android) 
            ? latestServerVersionData.downloadUrls.android 
            : window.location.origin + '/index.php/MobileApp/download/android';

        document.getElementById('btnDownloadUpdateApk').href = apkUrl;
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
            const currentCode = window.CURRENT_APP_VERSION_CODE || 8;
            const currentVersion = window.CURRENT_APP_VERSION || '1.0.7';
            if (latestServerVersionData && latestServerVersionData.versionCode > currentCode) {
                window.showAppUpdateModal();
            } else {
                alert(`✅ У вас установлена актуальная версия приложения: Работомер v${currentVersion}`);
            }
        }
    };

    // --- 4. ОСНОВНОЙ ЦИКЛ ПРОВЕРКИ (HEARTBEAT) ---
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
                
                // Успешная связь!
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
