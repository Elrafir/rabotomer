with open('/home/alexey/www/time-android/www/js/ui.js', 'r', encoding='utf-8') as f:
    code = f.read()

target_section = """// 11. Личный кабинет и сервер
window.openProfileModal = function() {
    document.getElementById('profileModal').classList.remove('hidden');
};

window.closeProfileModal = function() {
    document.getElementById('profileModal').classList.add('hidden');
};

window.openSyncModal = function() {
    window.openProfileModal();
};

window.saveAndTestServerUrl = async function() {
    const url = document.getElementById('settingServerUrl').value.trim();
    if (!url) return;

    if (window.rabotomerSync) {
        await window.rabotomerSync.setServerUrl(url);
        await window.rabotomerSync.sync();
        alert('Настройки сервера сохранены! Синхронизация запущена.');
    }
};

window.triggerManualSync = async function() {
    if (window.rabotomerSync) {
        await window.rabotomerSync.sync();
    }
};

window.setAvatarGender = function(gender) {
    const avatarSrc = gender === 'female' ? 'assets/img/avatar_female.png' : 'assets/img/avatar_male.png';
    const headerImg = document.getElementById('headerAvatarImg');
    const modalImg = document.getElementById('profileModalAvatar');
    if (headerImg) headerImg.src = avatarSrc;
    if (modalImg) modalImg.src = avatarSrc;
    window.db.setSetting('user_gender', gender);
};"""

replacement_section = """// 11. Личный кабинет (Профиль)
window.openProfileModal = async function() {
    const modal = document.getElementById('profileModal');
    if (!modal) return;
    
    // Load current user gender & avatar
    const gender = await window.db.getSetting('user_gender', 'male');
    const avatarSrc = gender === 'female' ? 'assets/img/avatar_female.png' : 'assets/img/avatar_male.png';
    const modalAvatar = document.getElementById('profileModalAvatar');
    if (modalAvatar) modalAvatar.src = avatarSrc;
    
    const radio = modal.querySelector(`input[name="user_gender"][value="${gender}"]`);
    if (radio) radio.checked = true;

    // Load modularity toggles
    const hideFin = await window.db.getSetting('hide_finance', '0');
    const chkFin = document.getElementById('settingHideFinance');
    if (chkFin) chkFin.checked = hideFin === '1';

    const showTl = await window.db.getSetting('show_timeline', '1');
    const chkTl = document.getElementById('settingShowTimeline');
    if (chkTl) chkTl.checked = showTl === '1';

    modal.classList.remove('hidden');
};

window.closeProfileModal = function() {
    const modal = document.getElementById('profileModal');
    if (modal) modal.classList.add('hidden');
};

// 11.1. Выделенный модуль связи и синхронизации
window.openServerSyncModal = async function() {
    const modal = document.getElementById('serverSyncModal');
    if (!modal) return;

    const inputUrl = document.getElementById('settingServerUrl');
    if (inputUrl && window.rabotomerSync) {
        inputUrl.value = window.rabotomerSync.serverUrl || 'http://192.168.100.2:7880';
    }

    window.updateSyncModalStatusUI();
    modal.classList.remove('hidden');
};

window.closeServerSyncModal = function() {
    const modal = document.getElementById('serverSyncModal');
    if (modal) modal.classList.add('hidden');
};

window.openSyncModal = function() {
    window.openServerSyncModal();
};

window.updateSyncModalStatusUI = function(state = null) {
    if (!window.rabotomerSync) return;
    const s = state || {
        status: window.rabotomerSync.status,
        lastSyncedAt: window.rabotomerSync.lastSyncedAt,
        lastPingMs: window.rabotomerSync.lastPingMs,
        error: window.rabotomerSync.lastError
    };

    const badge = document.getElementById('syncModalStatusBadge');
    const pingEl = document.getElementById('syncModalPingText');
    const lastEl = document.getElementById('syncModalLastSyncText');
    const errBox = document.getElementById('syncModalErrorBox');

    if (badge) {
        if (s.status === 'online_synced') {
            badge.className = 'flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800';
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-green-500"></span><span>В сети (Синхронизировано)</span>';
        } else if (s.status === 'syncing') {
            badge.className = 'flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800';
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span><span>Синхронизация данных...</span>';
        } else if (s.status === 'connecting') {
            badge.className = 'flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800';
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span><span>Подключение к серверу...</span>';
        } else {
            badge.className = 'flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800';
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-red-500"></span><span>Ошибка связи (Офлайн)</span>';
        }
    }

    if (pingEl) {
        if (s.lastPingMs !== null && s.status === 'online_synced') {
            pingEl.innerHTML = `⚡ Задержка (пинг): <b class="text-green-700">${s.lastPingMs} ms</b>`;
        } else {
            pingEl.innerHTML = `⚡ Задержка (пинг): <b class="text-gray-500">-</b>`;
        }
    }

    if (lastEl) {
        if (s.lastSyncedAt > 0) {
            const d = new Date(s.lastSyncedAt);
            const timeStr = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            lastEl.innerHTML = `🕒 Синхр.: <b class="text-gray-900">${timeStr}</b>`;
        } else {
            lastEl.innerHTML = `🕒 Синхр.: <b class="text-gray-500">нет</b>`;
        }
    }

    if (errBox) {
        if (s.error && s.status !== 'online_synced') {
            errBox.innerText = `⚠️ ${s.error}`;
            errBox.classList.remove('hidden');
        } else {
            errBox.classList.add('hidden');
        }
    }
};

window.quickSetServerUrl = async function(url) {
    const inputUrl = document.getElementById('settingServerUrl');
    if (inputUrl) inputUrl.value = url;
    await window.saveAndTestServerUrl();
};

window.saveAndTestServerUrl = async function() {
    const input = document.getElementById('settingServerUrl');
    if (!input) return;
    const url = input.value.trim();
    if (!url) {
        alert('Пожалуйста, укажите корректный URL сервера');
        return;
    }

    if (window.rabotomerSync) {
        const pingEl = document.getElementById('syncModalPingText');
        if (pingEl) pingEl.innerHTML = '⚡ Задержка (пинг): <b class="text-blue-600 animate-pulse">Проверка...</b>';
        
        await window.rabotomerSync.setServerUrl(url);
        const res = await window.rabotomerSync.sync(true);
        window.updateSyncModalStatusUI();

        if (res.success) {
            alert(`✅ Связь с сервером успешно установлена! (${url})`);
        } else {
            alert(`⚠️ Сервер по адресу ${url} недоступен: ${res.error || 'Проверьте сеть'}`);
        }
    }
};

window.startSubnetAutoDiscovery = async function() {
    const btn = document.getElementById('btnStartScanSubnet');
    const container = document.getElementById('scanProgressContainer');
    const progressBar = document.getElementById('scanProgressBar');
    const progressText = document.getElementById('scanProgressText');
    const progressPct = document.getElementById('scanProgressPercent');

    if (!window.rabotomerSync) return;

    if (btn) btn.disabled = true;
    if (container) container.classList.remove('hidden');

    const found = await window.rabotomerSync.discoverServer((text, pct) => {
        if (progressText) progressText.innerText = text;
        if (progressPct) progressPct.innerText = `${pct}%`;
        if (progressBar) progressBar.style.width = `${pct}%`;
    });

    if (btn) btn.disabled = false;

    if (found) {
        const input = document.getElementById('settingServerUrl');
        if (input) input.value = found;
        if (progressText) progressText.innerText = `✅ Найден сервер: ${found}`;
        if (progressPct) progressPct.innerText = `100%`;
        if (progressBar) progressBar.style.width = `100%`;
        window.updateSyncModalStatusUI();
        alert(`🎉 Сервер успешно обнаружен в сети!\nАдрес: ${found}`);
    } else {
        if (progressText) progressText.innerText = 'Сервер не найден в локальной подсети.';
        alert('Сервер не обнаружен. Убедитесь, что сервер запущен и устройство подключено к той же Wi-Fi сети / Хотспоту.');
    }
};

window.triggerManualSync = async function() {
    if (window.rabotomerSync) {
        const res = await window.rabotomerSync.sync();
        window.updateSyncModalStatusUI();
        if (res.success) {
            alert('✅ Синхронизация успешно завершена!');
        } else {
            alert(`⚠️ Ошибка синхронизации: ${res.error || 'Сервер недоступен'}`);
        }
    }
};

window.triggerBootstrapSync = async function() {
    if (!confirm('Выполнить полную выгрузку базы с сервера? Локальные данные будут обновлены данными с сервера.')) {
        return;
    }
    if (window.rabotomerSync) {
        try {
            await window.rabotomerSync.bootstrap();
            if (window.ui) window.ui.renderAll();
            window.updateSyncModalStatusUI();
            alert('✅ База данных успешно перезагружена с сервера!');
        } catch (e) {
            alert(`⚠️ Ошибка загрузки базы: ${e.message}`);
        }
    }
};

window.setAvatarGender = function(gender) {
    const avatarSrc = gender === 'female' ? 'assets/img/avatar_female.png' : 'assets/img/avatar_male.png';
    const headerImg = document.getElementById('headerAvatarImg');
    const modalImg = document.getElementById('profileModalAvatar');
    if (headerImg) headerImg.src = avatarSrc;
    if (modalImg) modalImg.src = avatarSrc;
    window.db.setSetting('user_gender', gender);
};"""

if target_section in code:
    code = code.replace(target_section, replacement_section)
    with open('/home/alexey/www/time-android/www/js/ui.js', 'w', encoding='utf-8') as f:
        f.write(code)
    print("Updated ui.js successfully!")
else:
    print("Could not find exact target section in ui.js")
