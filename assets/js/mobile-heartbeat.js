/**
 * Mobile Heartbeat & Offline Disconnect Manager
 * Отслеживает доступность сервера и показывает модальное окно при потере связи.
 */
(function() {
    let consecutiveFailures = 0;
    const MAX_FAILURES = 2;
    const CHECK_INTERVAL = 8000; // 8 секунд
    let isChecking = false;
    let modalElement = null;

    function createModal() {
        if (modalElement) return;

        modalElement = document.createElement('div');
        modalElement.id = 'mobileOfflineModal';
        modalElement.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(8px);
            z-index: 999999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #f8fafc;
        `;

        modalElement.innerHTML = `
            <div style="
                background: #1e293b;
                border: 1px solid #334155;
                border-radius: 16px;
                padding: 24px;
                max-width: 380px;
                width: 100%;
                text-align: center;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            ">
                <div style="font-size: 40px; margin-bottom: 12px;">⚠️</div>
                <h3 style="margin: 0 0 8px 0; font-size: 20px; font-weight: 700; color: #f8fafc;">Связь с сервером потеряна</h3>
                <p style="margin: 0 0 20px 0; font-size: 14px; color: #94a3b8; line-height: 1.5;">
                    Приложение не может связаться с сервером.<br>
                    <small style="font-size: 12px; color: #64748b;" id="offlineServerOrigin"></small>
                </p>
                
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button id="btnRetryHeartbeat" style="
                        width: 100%;
                        background-color: #2563eb;
                        color: white;
                        border: none;
                        padding: 12px;
                        border-radius: 10px;
                        font-size: 15px;
                        font-weight: 600;
                        cursor: pointer;
                    ">🔄 Повторить попытку</button>
                    
                    <button id="btnResetServerUrl" style="
                        width: 100%;
                        background-color: #334155;
                        color: #cbd5e1;
                        border: none;
                        padding: 12px;
                        border-radius: 10px;
                        font-size: 14px;
                        font-weight: 600;
                        cursor: pointer;
                    ">⚙️ Сменить адрес сервера</button>
                </div>
            </div>
        `;

        document.body.appendChild(modalElement);

        document.getElementById('btnRetryHeartbeat').addEventListener('click', function() {
            const btn = this;
            btn.innerText = '⏳ Проверка связи...';
            btn.disabled = true;
            checkServerHealth().then(isOk => {
                btn.innerText = '🔄 Повторить попытку';
                btn.disabled = false;
                if (isOk) hideModal();
            });
        });

        document.getElementById('btnResetServerUrl').addEventListener('click', function() {
            // Очищаем сохраненный URL в localStorage если возможно
            try {
                localStorage.removeItem('timeTrackerServerUrl');
            } catch(e) {}
            // Перенаправляем на локальный стартовый экран Capacitor / Cordova или сброс
            window.location.href = window.location.origin + '/MobileApp/reset_setup';
        });
    }

    function showModal() {
        createModal();
        const originEl = document.getElementById('offlineServerOrigin');
        if (originEl) originEl.innerText = window.location.origin;
        if (modalElement) modalElement.style.display = 'flex';
    }

    function hideModal() {
        if (modalElement) modalElement.style.display = 'none';
        consecutiveFailures = 0;
    }

    async function checkServerHealth() {
        if (isChecking) return true;
        isChecking = true;

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3500);

        try {
            const pingUrl = window.location.origin + '/MobileApp/version?t=' + Date.now();
            const response = await fetch(pingUrl, {
                method: 'GET',
                signal: controller.signal,
                cache: 'no-store'
            });
            clearTimeout(timeoutId);
            isChecking = false;

            if (response.ok || response.status === 200) {
                consecutiveFailures = 0;
                hideModal();
                return true;
            } else {
                consecutiveFailures++;
            }
        } catch (e) {
            clearTimeout(timeoutId);
            isChecking = false;
            consecutiveFailures++;
        }

        if (consecutiveFailures >= MAX_FAILURES) {
            showModal();
            return false;
        }
        return true;
    }

    // Запускаем фоновый таймер
    window.addEventListener('DOMContentLoaded', () => {
        // Первую проверку делаем через 5 секунд
        setTimeout(checkServerHealth, 5000);
        setInterval(checkServerHealth, CHECK_INTERVAL);
    });

    // Реакция на онлайн/оффлайн события браузера
    window.addEventListener('offline', () => {
        consecutiveFailures = MAX_FAILURES;
        showModal();
    });

    window.addEventListener('online', () => {
        checkServerHealth();
    });
})();
