// Обертка над IndexedDB для хранения действий
const OfflineSync = {
    dbName: 'RabotomerDB',
    storeName: 'offlineActions',
    db: null,

    init: function() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, 1);
            
            request.onerror = (event) => {
                console.error('IndexedDB error:', event.target.error);
                reject(event.target.error);
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                resolve();
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(this.storeName)) {
                    db.createObjectStore(this.storeName, { keyPath: 'id', autoIncrement: true });
                }
            };
        });
    },

    // Сохранить действие (например: { type: 'START', taskId: 5, timestamp: '2023-10-10 14:00:00' })
    addAction: function(action) {
        return new Promise((resolve, reject) => {
            if (!this.db) { reject('DB not initialized'); return; }
            const transaction = this.db.transaction([this.storeName], 'readwrite');
            const store = transaction.objectStore(this.storeName);
            action.savedAt = new Date().getTime(); // Добавляем метку времени сохранения
            const request = store.add(action);
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },

    // Получить все действия по порядку
    getAllActions: function() {
        return new Promise((resolve, reject) => {
            if (!this.db) { reject('DB not initialized'); return; }
            const transaction = this.db.transaction([this.storeName], 'readonly');
            const store = transaction.objectStore(this.storeName);
            const request = store.getAll();
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },

    // Очистить все действия
    clearActions: function() {
        return new Promise((resolve, reject) => {
            if (!this.db) { reject('DB not initialized'); return; }
            const transaction = this.db.transaction([this.storeName], 'readwrite');
            const store = transaction.objectStore(this.storeName);
            const request = store.clear();
            
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    },
    
    // Получение текущего времени в формате YYYY-MM-DD HH:mm:ss для БД
    getFormattedDate: function() {
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        const h = String(now.getHours()).padStart(2, '0');
        const min = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd} ${h}:${min}:${s}`;
    }
};

// Инициализация при загрузке скрипта
OfflineSync.init().then(() => {
    // Пытаемся синхронизировать при загрузке
    if (navigator.onLine) {
        syncOfflineData();
    }
});

// Слушаем появление интернета
window.addEventListener('online', () => {
    console.log('Появилось соединение! Запуск синхронизации...');
    syncOfflineData();
});

// Функция отправки данных на сервер
function syncOfflineData() {
    OfflineSync.getAllActions().then(actions => {
        if (actions.length === 0) return; // Нечего синхронизировать
        
        console.log(`Найдено ${actions.length} оффлайн действий. Синхронизируем...`);
        
        // Отправляем пакет на сервер
        $.ajax({
            url: window.api ? window.api.sync_offline : '/index.php/tasks/sync_offline_actions_ajax',
            type: 'POST',
            data: { actions: JSON.stringify(actions) },
            success: function(response) {
                try {
                    let res = JSON.parse(response);
                    if (res.status === 'success') {
                        // Очищаем локальную базу
                        OfflineSync.clearActions().then(() => {
                            console.log('Синхронизация успешна, локальная база очищена.');
                            // Перезагружаем страницу, чтобы подтянуть актуальное состояние с сервера
                            window.location.reload();
                        });
                    }
                } catch(e) {
                    console.error('Ошибка разбора ответа при синхронизации', e);
                }
            },
            error: function() {
                console.error('Сервер недоступен для синхронизации, оставляем в очереди.');
            }
        });
    });
}
