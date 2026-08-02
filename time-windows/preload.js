const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('electronAPI', {
    // Настройки сервера (используются в settings.html и главном приложении)
    getServerUrl: () => ipcRenderer.invoke('get-server-url'),
    saveServerUrl: (url) => ipcRenderer.send('save-server-url', url),
    
    // Синхронизация состояния таймера (вызывается из timer.js сайта)
    updateTimerState: (stateInfo) => ipcRenderer.send('update-timer-state', stateInfo),
    
    // Слушатели команд от системного лотка/горячих клавиш (приемники в timer.js)
    onTimerAction: (callback) => ipcRenderer.on('timer-action', (event, action) => callback(action)),
    
    // Открыть окно настроек из главного интерфейса
    openSettings: () => ipcRenderer.send('open-settings')
});
