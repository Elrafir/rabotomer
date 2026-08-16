with open('/home/alexey/www/time-android/www/js/ui.js', 'r', encoding='utf-8') as f:
    code = f.read()

old_func = """    onSyncStateChange(syncState) {
        const dot = document.getElementById('syncStatusDot');
        const text = document.getElementById('syncStatusText');
        if (!dot || !text) return;

        if (syncState.status === 'syncing') {
            dot.className = 'w-2.5 h-2.5 rounded-full bg-yellow-400 animate-spin';
            text.textContent = 'Синхронизация...';
        } else if (syncState.status === 'online_synced') {
            dot.className = 'w-2.5 h-2.5 rounded-full bg-green-400';
            text.textContent = 'В сети (синхр.)';
        } else if (syncState.status === 'offline') {
            dot.className = 'w-2.5 h-2.5 rounded-full bg-gray-400';
            text.textContent = 'Офлайн режим';
        } else if (syncState.status === 'error') {
            dot.className = 'w-2.5 h-2.5 rounded-full bg-red-400';
            text.textContent = 'Ошибка связи';
        }
    }"""

new_func = """    onSyncStateChange(syncState) {
        const dot = document.getElementById('syncStatusDot');
        const text = document.getElementById('syncStatusText');
        if (dot && text) {
            if (syncState.status === 'syncing') {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-yellow-400 animate-pulse';
                text.textContent = 'Синхронизация...';
            } else if (syncState.status === 'online_synced') {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-green-400';
                text.textContent = 'В сети (синхр.)';
            } else if (syncState.status === 'offline') {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-gray-400';
                text.textContent = 'Офлайн режим';
            } else if (syncState.status === 'error') {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-red-400';
                text.textContent = 'Ошибка связи';
            }
        }

        if (typeof window.updateSyncModalStatusUI === 'function') {
            window.updateSyncModalStatusUI(syncState);
        }
    }"""

if old_func in code:
    code = code.replace(old_func, new_func)
    with open('/home/alexey/www/time-android/www/js/ui.js', 'w', encoding='utf-8') as f:
        f.write(code)
    print("Updated onSyncStateChange in ui.js successfully!")
else:
    print("Could not find old onSyncStateChange")
