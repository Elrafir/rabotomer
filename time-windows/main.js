const { app, BrowserWindow, ipcMain, Tray, Menu, globalShortcut, screen } = require('electron');
const path = require('path');

let store;
let mainWindow = null;
let widgetWindow = null;
let tray = null;
let isQuitting = false;

// Таймер состояния (чтобы локально отсчитывать секунды для виджета без постоянного дерганья из веба)
let timerState = { state: 'stopped', timeSeconds: 0, taskName: '' };
let localTimerInterval = null;

async function initStore() {
    const Store = (await import('electron-store')).default;
    store = new Store();
}

function createMainWindow() {
    const serverUrl = store.get('serverUrl');
    
    mainWindow = new BrowserWindow({
        width: 1200,
        height: 800,
        minWidth: 800,
        minHeight: 600,
        title: 'Работомер',
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            contextIsolation: true,
            nodeIntegration: false
        }
    });

    if (serverUrl) {
        mainWindow.loadURL(serverUrl).catch(e => {
            console.error("Failed to load URL:", e);
            mainWindow.loadFile('settings.html');
        });
        
        // Проверка обновлений
        checkWindowsUpdate(serverUrl);
    } else {
        mainWindow.loadFile('settings.html');
    }

    mainWindow.on('close', (event) => {

        if (!isQuitting) {
            event.preventDefault();
            mainWindow.hide();
            // Показываем виджет, если таймер запущен
            if (timerState.state !== 'stopped') {
                widgetWindow.show();
            }
        }
    });

    // Обработка горячих клавиш F5 и Ctrl+R только когда окно в фокусе
    mainWindow.webContents.on('before-input-event', (event, input) => {
        if (input.type === 'keyDown') {
            if (input.key === 'F5' || (input.control && input.key.toLowerCase() === 'r')) {
                mainWindow.reload();
                event.preventDefault();
            }
        }
    });
}

function createWidgetWindow() {
    const primaryDisplay = screen.getPrimaryDisplay();
    const { width, height } = primaryDisplay.workAreaSize;
    
    // Создаем окно без рамок, прозрачное
    widgetWindow = new BrowserWindow({
        width: 200,
        height: 80,
        x: width - 220, // В правом нижнем углу
        y: height - 100,
        frame: false,
        transparent: true,
        alwaysOnTop: true,
        skipTaskbar: true,
        show: false, // Изначально скрыто
        webPreferences: {
            nodeIntegration: true,
            contextIsolation: false
        }
    });
    
    widgetWindow.loadFile('widget.html');
}

function updateTrayMenu() {
    const contextMenu = Menu.buildFromTemplate([
        { 
            label: 'Развернуть', 
            click: () => { 
                mainWindow.show(); 
                widgetWindow.hide();
            } 
        },
        { type: 'separator' },
        { 
            label: timerState.state === 'running' ? 'Поставить на паузу' : 'Продолжить', 
            enabled: timerState.state !== 'stopped',
            click: () => {
                if (mainWindow) mainWindow.webContents.send('timer-action', 'toggle');
            }
        },
        { 
            label: 'Остановить', 
            enabled: timerState.state !== 'stopped',
            click: () => {
                if (mainWindow) mainWindow.webContents.send('timer-action', 'stop');
            }
        },
        { type: 'separator' },
        {
            label: 'Обновить страницу',
            click: () => {
                if (mainWindow) {
                    mainWindow.reload();
                    if (!mainWindow.isVisible()) mainWindow.show();
                }
            }
        },
        { 
            label: 'Настройки сервера...', 
            click: () => {
                mainWindow.loadFile('settings.html');
                mainWindow.show();
                widgetWindow.hide();
            } 
        },
        { 
            label: 'Выход', 
            click: () => { 
                isQuitting = true; 
                app.quit(); 
            } 
        }
    ]);
    
    tray.setContextMenu(contextMenu);
    
    if (timerState.state === 'running') {
        tray.setToolTip(`Таймер идет (${timerState.taskName})`);
    } else if (timerState.state === 'paused') {
        tray.setToolTip(`Таймер на паузе (${timerState.taskName})`);
    } else {
        tray.setToolTip('Работомер - Нет активных задач');
    }
}

function createTray() {
    // В собранном приложении используем нашу скопированную иконку
    const iconPath = path.join(__dirname, 'tray_icon.png');
    tray = new Tray(iconPath);
    updateTrayMenu();

    tray.on('click', () => {
        if (mainWindow.isVisible()) {
            mainWindow.hide();
            if (timerState.state !== 'stopped') widgetWindow.show();
        } else {
            mainWindow.show();
            widgetWindow.hide();
        }
    });
}

function startLocalTimer() {
    if (localTimerInterval) clearInterval(localTimerInterval);
    localTimerInterval = setInterval(() => {
        if (timerState.state === 'running') {
            timerState.timeSeconds++;
            if (widgetWindow) widgetWindow.webContents.send('update-widget', timerState);
        }
    }, 1000);
}

const gotTheLock = app.requestSingleInstanceLock();
if (!gotTheLock) {
    app.quit();
} else {
    app.on('second-instance', (event, commandLine, workingDirectory) => {
        if (mainWindow) {
            if (mainWindow.isMinimized()) mainWindow.restore();
            if (!mainWindow.isVisible()) mainWindow.show();
            mainWindow.focus();
        }
    });

    app.whenReady().then(async () => {
        await initStore();
        createMainWindow();
        createWidgetWindow();
        createTray();

        // Глобальные горячие клавиши
        globalShortcut.register('CommandOrControl+Shift+T', () => {
            if (mainWindow && timerState.state !== 'stopped') {
                mainWindow.webContents.send('timer-action', 'toggle');
            }
        });

        app.on('activate', () => {
            if (BrowserWindow.getAllWindows().length === 0) createMainWindow();
        });
        
        startLocalTimer();
    });
}



app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

// IPC События от настроек (settings.html)
ipcMain.handle('get-server-url', () => {
    return store.get('serverUrl') || '';
});

ipcMain.on('save-server-url', (event, url) => {
    store.set('serverUrl', url);
    mainWindow.loadURL(url);
});

ipcMain.on('open-settings', () => {
    mainWindow.loadFile('settings.html');
});

// IPC События от сайта (timer.js)
ipcMain.on('update-timer-state', (event, stateInfo) => {
    timerState = stateInfo; // { state: 'running'|'paused'|'stopped', timeSeconds: 123, taskName: 'Title' }
    updateTrayMenu();
    
    // Обновляем виджет
    if (widgetWindow) {
        widgetWindow.webContents.send('update-widget', timerState);
        if (timerState.state === 'stopped') {
            widgetWindow.hide();
        } else if (!mainWindow.isVisible()) {
            widgetWindow.show();
        }
    }
});

// IPC События от виджета (widget.html)
ipcMain.on('widget-action', (event, action) => {
    if (action === 'restore') {
        mainWindow.show();
        widgetWindow.hide();
    } else if (action === 'toggle' || action === 'stop') {
        if (mainWindow) mainWindow.webContents.send('timer-action', action);
    }
});

function checkWindowsUpdate(serverUrl) {
    const { dialog, shell } = require('electron');
    const apiUrl = new URL('/MobileApp/version', serverUrl).toString();
    
    // Используем встроенный Node.js fetch (доступен в Node 18+)
    fetch(apiUrl)
        .then(res => res.json())
        .then(data => {
            const currentVersion = app.getVersion(); // из package.json
            const serverVersion = data.version;
            
            // Простейшее сравнение строк (1.0.1 > 1.0.0)
            if (serverVersion && serverVersion !== currentVersion && serverVersion.localeCompare(currentVersion, undefined, { numeric: true }) > 0) {
                dialog.showMessageBox(mainWindow, {
                    type: 'info',
                    title: 'Доступно обновление',
                    message: `Вышла новая версия приложения: ${serverVersion}\n\nТекущая версия: ${currentVersion}`,
                    detail: data.releaseNotes || 'Рекомендуется обновить приложение для стабильной работы.',
                    buttons: ['Скачать обновление', 'Позже'],
                    defaultId: 0,
                    cancelId: 1
                }).then(result => {
                    if (result.response === 0 && data.downloadUrls && data.downloadUrls.windows) {
                        shell.openExternal(data.downloadUrls.windows);
                    }
                });
            }
        })
        .catch(err => console.error('Ошибка проверки обновлений:', err));
}
