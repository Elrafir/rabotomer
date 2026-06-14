// Hover accordion logic was removed from here.

// --- GLOBAL TIMER LOGIC ---
/** @type {number|null} Глобальный интервал таймера (1 раз в секунду) */
let globalTimerInterval = null;
/** @type {number|null} Интервал мигания тайтла браузера при паузе */
let globalTitleInterval = null;
/** @type {number|null} Timestamp момента старта таймера (локально в браузере) */
let localStartTime = null;
/** @type {number} Количество секунд, прошедшее в текущей сессии на момент загрузки страницы */
let initialSessionSeconds = 0;
/** @type {number} Суммарное накопленное время задачи за прошлые сессии (в секундах) */
let totalAccumulatedSeconds = 0;
/** @type {string} Название текущей активной задачи */
let currentTaskTitle = "";
/** @type {string|number|null} Идентификатор текущей активной задачи */
let currentTaskId = null;
/** @type {boolean} Флаг состояния паузы */
let isPaused = false;
/** @type {boolean} Флаг для мигания текста в тайтле */
let blinkState = false;

/**
 * Форматирует время из секунд в формат HH:MM:SS
 * @param {number} totalSeconds Количество секунд
 * @returns {string} Строка в формате HH:MM:SS
 */
function formatTime(totalSeconds) {
    const h = Math.floor(totalSeconds / 3600).toString().padStart(2, '0');
    const m = Math.floor((totalSeconds % 3600) / 60).toString().padStart(2, '0');
    const s = (totalSeconds % 60).toString().padStart(2, '0');
    return `${h}:${m}:${s}`;
}

// Функции для динамической иконки (Favicon)
function createCircleFavicon(color) {
    const canvas = document.createElement('canvas');
    canvas.width = 32;
    canvas.height = 32;
    const ctx = canvas.getContext('2d');
    ctx.beginPath();
    ctx.arc(16, 16, 14, 0, 2 * Math.PI);
    ctx.fillStyle = color;
    ctx.fill();
    return canvas.toDataURL('image/png');
}

const faviconGreen = createCircleFavicon('#10B981');
const faviconRed = createCircleFavicon('#EF4444');
const faviconDefault = 'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⏱️</text></svg>';

function setFavicon(url) {
    let link = document.querySelector("link[rel~='icon']");
    if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        document.head.appendChild(link);
    }
    link.href = url;
}

/**
 * Обновляет дисплеи таймера (в виджете и панели) на основе прошедшего времени
 */
function updateGlobalTimerDisplay() {
    if (localStartTime === null) return;
    
    // Рассчитываем сколько реально прошло секунд с момента загрузки скрипта
    const elapsedSinceLoad = Math.floor((Date.now() - localStartTime) / 1000);
    const currentSessionSeconds = initialSessionSeconds + elapsedSinceLoad;
    
    if (currentSessionSeconds >= 0) {
        const timeStr = formatTime(currentSessionSeconds);
        const totalStr = formatTime(totalAccumulatedSeconds + currentSessionSeconds);
        
        // Обновляем текст в нижней панели (теперь она есть на всех страницах)
        $('#timerDisplay').text(timeStr);
        $('#totalTimerDisplay').text(totalStr);
        
        // Обновляем плавающий виджет
        $('#globalWidgetTimer').text(timeStr);
        
        // Обновляем заголовок браузера
        if (!isPaused) {
            document.title = `🟢 ${timeStr} - ${currentTaskTitle}`;
            setFavicon(faviconGreen);
        }
    }
}

/**
 * Заставляет тайтл страницы мигать, если таймер находится на паузе
 */
function blinkPausedTitle() {
    if (!isPaused) return;
    const timeStr = formatTime(initialSessionSeconds); // Время заморожено
    blinkState = !blinkState;
    document.title = blinkState ? `🔴 ПАУЗА - ${currentTaskTitle}` : `🔴 ${timeStr} - ${currentTaskTitle}`;
    setFavicon(blinkState ? faviconRed : faviconDefault);
}

/**
 * Инициализирует глобальный таймер при загрузке (или перезагрузке) страницы.
 * Проверяет наличие активной сессии от сервера или сохраненного состояния паузы.
 */
function initGlobalTimer() {
    if (globalTimerInterval) clearInterval(globalTimerInterval);
    if (globalTitleInterval) clearInterval(globalTitleInterval);
    
    // Удаляем старый костыль
    localStorage.removeItem('pausedTimerInfo');

    if (window.globalActiveSession) {
        // Активная сессия от сервера существует!
        isPaused = (window.globalActiveSession.is_paused == 1);
        
        currentTaskTitle = window.globalActiveSession.task_title;
        currentTaskId = window.globalActiveSession.task_id;
        initialSessionSeconds = parseInt(window.globalActiveSession.current_elapsed) || 0;
        totalAccumulatedSeconds = parseInt(window.globalActiveSession.total_accumulated) || 0;
        localStartTime = Date.now();
        
        showTimerUI();
        
        if (isPaused) {
            globalTitleInterval = setInterval(blinkPausedTitle, 1000);
            blinkPausedTitle();
        } else {
            updateGlobalTimerDisplay();
            globalTimerInterval = setInterval(updateGlobalTimerDisplay, 1000);
            setFavicon(faviconGreen);
        }
    } else {
        // Таймер полностью остановлен
        isPaused = false;
        hideTimerUI();
        document.title = 'Тайм-трекер';
        setFavicon(faviconDefault);
    }
}

/**
 * Обновляет визуальное состояние UI элементов таймера.
 * По умолчанию всегда показывает только плавающий виджет. 
 * Если нажата пауза, обновляет кнопку.
 */
function showTimerUI() {
    $('#globalWidgetTitle').text(currentTaskTitle);
    $('#activeTimerTitle').text(currentTaskTitle);
    
    if (window.isTimerPanelOpen) {
        toggleTimerPanelDOM(true);
    } else {
        // По умолчанию показываем только виджет, а панель прячем
        $('#activeTimerPanel').removeClass('translate-y-0').addClass('translate-y-full');
        
        // Анимированное появление
        $('#globalFloatingWidgetContainer').removeClass('hidden');
        setTimeout(() => {
            $('#globalFloatingWidgetContainer')
                .removeClass('opacity-0 scale-50 pointer-events-none')
                .addClass('opacity-100 scale-100');
        }, 10);
    }
    
    if (isPaused) {
        $('#timerDisplay').text(formatTime(initialSessionSeconds));
        $('#totalTimerDisplay').text(formatTime(totalAccumulatedSeconds + initialSessionSeconds));
        // Обновляем кнопку в панели
        $('#btnPauseDashboard').html(window.globalLang.btn_continue).removeClass('bg-yellow-500 hover:bg-yellow-400').addClass('bg-green-500 hover:bg-green-400');
        
        // Обновляем плавающий виджет
        $('#globalWidgetTimer').text(formatTime(initialSessionSeconds));
        $('#globalFloatingWidget')
            .addClass('widget-paused-bg animate-pulse');
            
        // Показываем красную кнопку "Стоп"
        $('#globalWidgetStopBtn')
            .removeClass('opacity-0 scale-50 pointer-events-none')
            .addClass('opacity-100 scale-100');
            
        $('#globalWidgetIcon').html('⏸'); // Иконка паузы
        
        // Обновляем логотип в шапке (состояние паузы)
        $('#logo-emoji').text('⏸️');
        $('#logo-svg').removeClass('text-white text-green-300').addClass('text-yellow-300 animate-pulse');
        $('#logo-title').removeClass('text-white text-green-300').addClass('text-yellow-300 animate-pulse');
        
    } else {
        // Если не на паузе, возвращаем стандартные классы
        $('#btnPauseDashboard').html(window.globalLang.btn_pause).removeClass('bg-green-500 hover:bg-green-400').addClass('bg-yellow-500 hover:bg-yellow-400');
        $('#globalFloatingWidget')
            .removeClass('widget-paused-bg animate-pulse');
            
        // Прячем красную кнопку "Стоп"
        $('#globalWidgetStopBtn')
            .removeClass('opacity-100 scale-100')
            .addClass('opacity-0 scale-50 pointer-events-none');
            
        // SVG иконка циферблата с крутящейся стрелкой
        $('#globalWidgetIcon').html('<svg class="w-5 h-5 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2.5"></circle><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6l3.5 2" class="anim-spin-slow" style="transform-origin: 12px 12px;"></path></svg>');
        
        // Обновляем логотип в шапке (активное состояние)
        $('#logo-emoji').text('⏱️');
        $('#logo-svg').removeClass('text-white text-yellow-300 animate-pulse').addClass('text-green-300');
        $('#logo-title').removeClass('text-white text-yellow-300 animate-pulse').addClass('text-green-300');
    }
}

/**
 * Скрывает все элементы UI таймера (когда таймер не активен).
 */
function hideTimerUI() {
    $('#activeTimerPanel').removeClass('translate-y-0').addClass('translate-y-full');
    
    // Возвращаем логотип в исходное (неактивное) состояние
    $('#logo-emoji').text('💼');
    $('#logo-svg').removeClass('text-green-300 text-yellow-300 animate-pulse').addClass('text-white');
    $('#logo-title').removeClass('text-green-300 text-yellow-300 animate-pulse').addClass('text-white');
    
    // Анимированное скрытие плавающего виджета
    $('#globalFloatingWidgetContainer')
        .removeClass('opacity-100 scale-100')
        .addClass('opacity-0 scale-50 pointer-events-none');
        
    setTimeout(() => {
        $('#globalFloatingWidgetContainer').addClass('hidden');
    }, 500); // задержка для завершения анимации
}

/**
 * Вспомогательная функция для переключения состояния панели (вкл/выкл).
 * Выполняет DOM-манипуляции без анимации.
 * @param {boolean} show Если true, показывает панель, иначе виджет
 */
function toggleTimerPanelDOM(show) {
    if (show) {
        $('#globalFloatingWidgetContainer').addClass('hidden');
        $('#btnCollapseTimerPanel').removeClass('hidden');
        $('#activeTimerPanel').removeClass('translate-y-full').addClass('translate-y-0');
    } else {
        $('#globalFloatingWidgetContainer').removeClass('hidden');
        $('#btnCollapseTimerPanel').addClass('hidden');
        $('#activeTimerPanel').removeClass('translate-y-0').addClass('translate-y-full');
    }
}

/**
 * Показывает полноразмерную нижнюю панель таймера и скрывает плавающий виджет.
 */
window.showTimerPanel = function() {
    window.isTimerPanelOpen = true;
    if (document.startViewTransition) {
        // Подготовка: назначаем имена для начального состояния
        $('#globalFloatingWidgetContainer').css('view-transition-name', 'timer-morph');
        $('#activeTimerPanel').css('view-transition-name', 'none').css('transition', 'none');
        
        document.startViewTransition(() => {
            // Переключаем DOM
            toggleTimerPanelDOM(true);
            
            // Назначаем имена для конечного состояния
            $('#globalFloatingWidgetContainer').css('view-transition-name', 'none');
            $('#activeTimerPanel').css('view-transition-name', 'timer-morph');
        });
    } else {
        toggleTimerPanelDOM(true);
    }
};

/**
 * Скрывает нижнюю панель таймера и возвращает плавающий виджет.
 */
window.hideTimerPanel = function() {
    window.isTimerPanelOpen = false;
    if (document.startViewTransition) {
        // Подготовка: назначаем имена для начального состояния
        $('#activeTimerPanel').css('view-transition-name', 'timer-morph').css('transition', 'none');
        $('#globalFloatingWidgetContainer').css('view-transition-name', 'none');
        
        document.startViewTransition(() => {
            // Переключаем DOM
            toggleTimerPanelDOM(false);
            
            // Назначаем имена для конечного состояния
            $('#activeTimerPanel').css('view-transition-name', 'none');
            $('#globalFloatingWidgetContainer').css('view-transition-name', 'timer-morph');
        });
    } else {
        toggleTimerPanelDOM(false);
    }
};

/**
 * Функция ставит или снимает текущую сессию с паузы.
 * Взаимодействует с API через AJAX.
 */
function actionTogglePause() {
    if (!currentTaskId) return;
    
    if (isPaused) {
        // Resume
        $.post(window.globalApi.start, { task_id: currentTaskId }, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                window.globalActiveSession = res.data; // Обновляем глобальную переменную
                initGlobalTimer(); // Применяем изменения к UI
                loadAjaxPage(window.location.href, false); // Reload via AJAX
            } else {
                alert(res.message);
            }
        });
    } else {
        // Pause
        $.post(window.globalApi.pause, {}, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                // Обновляем текущую сессию
                window.globalActiveSession = res.data;
                initGlobalTimer(); // Применяем изменения к UI (включит желтый мигающий цвет)
                loadAjaxPage(window.location.href, false); // Reload via AJAX
            } else {
                alert(res.message);
            }
        });
    }
}

/**
 * Функция останавливает текущую сессию с запросом комментария пользователя.
 */
function actionStopTimer() {
    const elapsedSinceLoad = Math.floor((Date.now() - localStartTime) / 1000);
    const currentSessionSeconds = isPaused ? initialSessionSeconds : initialSessionSeconds + elapsedSinceLoad;

    let note = "";
    // Запрашиваем комментарий только если сессия длится больше 1 минуты (не спам)
    if (currentSessionSeconds >= 60) {
        note = prompt(window.globalLang.js_prompt_stop_timer, "");
        if (note === null) return;
    }

    $.post(window.globalApi.stop, { note: note }, function(response) {
        let res = JSON.parse(response);
        if (res.status === 'success' || res.status === 'spam') {
            window.globalActiveSession = null;
            initGlobalTimer();
            loadAjaxPage(window.location.href, false); // Reload via AJAX
        } else {
            alert(res.message);
        }
    });
}

/**
 * Динамически перемещает кнопку "Стоп", чтобы она не вылезала за левый край экрана.
 */
function updateStopButtonPosition(x, wWidth) {
    const btn = $('#globalWidgetStopBtn');
    if (x < 65) {
        btn.css('left', (wWidth + 8) + 'px');
    } else {
        btn.css('left', '-56px');
    }
}


/**
 * Функция обертка для вызова паузы из глобального виджета.
 */
window.globalTogglePause = function() {
    actionTogglePause();
};

// Dashboard functions (used by onclick in dashboard.php)
window.pauseTimer = function() {
    actionTogglePause();
};
window.stopTimer = function() {
    actionStopTimer();
};

/**
 * Функция стартует новый таймер для указанной задачи.
 * @param {string|number} taskId Идентификатор задачи
 */
window.startTimer = function(taskId) {
    $.post(window.globalApi.start, { task_id: taskId }, function(response) {
        let res = JSON.parse(response);
        if (res.status === 'success') {
            localStorage.removeItem('pausedTimerInfo');
            window.globalActiveSession = res.data;
            initGlobalTimer();
            loadAjaxPage(window.location.href, false); // Reload via AJAX
        } else {
            alert(res.message);
        }
    });
};

// --- DRAG AND DROP WIDGET & PANEL DOCKING ---
$(document).ready(function() {
    initGlobalTimer();
    
    const container = $('#globalFloatingWidgetContainer');
    const widget = $('#globalFloatingWidget');
    const panel = $('#activeTimerPanel');
    
    if (container.length === 0) return;
    
    // Восстанавливаем позицию контейнера
    const savedPos = localStorage.getItem('widgetPos');
    if (savedPos) {
        try {
            const pos = JSON.parse(savedPos);
            if (pos && typeof pos.top === 'number' && typeof pos.left === 'number' && !isNaN(pos.top) && !isNaN(pos.left)) {
                let finalX = pos.left;
                let finalY = pos.top;
                
                const ww = window.innerWidth;
                const wh = window.innerHeight;
                const wWidth = 150;
                const wHeight = 50;
                
                if (finalX < 10) finalX = 10;
                if (finalY < 10) finalY = 10;
                if (finalX > ww - wWidth - 30) finalX = ww - wWidth - 30;
                if (finalY > wh - wHeight - 10) finalY = wh - wHeight - 10;
                
                container.css({top: finalY + 'px', left: finalX + 'px', right: 'auto', bottom: 'auto'});
                // Обновляем позицию кнопки
                updateStopButtonPosition(finalX, wWidth);
            } else {
                localStorage.removeItem('widgetPos');
            }
        } catch(e){}
    }
    
    let isDragging = false;
    let startX, startY, initialLeft, initialTop;
    let dragElement = null; // 'widget' или 'panel'
    
    container.css('touch-action', 'none');
    panel.css('touch-action', 'none');
    
    // --- ОБРАБОТЧИК ДЛЯ ВИДЖЕТА ---
    widget.on('pointerdown', function(e) {
        if (e.button !== 0 && e.button !== undefined) return;
        
        isDragging = true;
        dragElement = 'widget';
        const pos = container.position();
        initialLeft = pos.left;
        initialTop = pos.top;
        
        startX = e.originalEvent.clientX || e.clientX;
        startY = e.originalEvent.clientY || e.clientY;
        
        const pointerId = e.pointerId || (e.originalEvent && e.originalEvent.pointerId);
        if (pointerId !== undefined) {
            container[0].setPointerCapture(pointerId);
        }
        $('body').addClass('select-none');
        
        widget.removeClass('cursor-pointer').addClass('cursor-grabbing');
    });
    
    // --- ОБРАБОТЧИК ДЛЯ ПАНЕЛИ ---
    panel.on('pointerdown', function(e) {
        if ($(e.target).closest('button, input, a').length > 0) return;
        if (e.button !== 0 && e.button !== undefined) return;
        
        isDragging = true;
        dragElement = 'panel';
        
        startX = e.originalEvent.clientX || e.clientX;
        startY = e.originalEvent.clientY || e.clientY;
        
        initialTop = 0; 
        
        const pointerId = e.pointerId || (e.originalEvent && e.originalEvent.pointerId);
        if (pointerId !== undefined) {
            panel[0].setPointerCapture(pointerId);
        }
        $('body').addClass('select-none');
        
        panel.removeClass('transition-transform duration-300');
    });
    
    // --- ГЛОБАЛЬНОЕ ДВИЖЕНИЕ ---
    $(document).on('pointermove', function(e) {
        if (!isDragging) return;
        
        let clientX = e.originalEvent.clientX || e.clientX;
        let clientY = e.originalEvent.clientY || e.clientY;
        
        let dx = clientX - startX;
        let dy = clientY - startY;
        
        if (dragElement === 'widget') {
            const distance = Math.sqrt(dx * dx + dy * dy);
            if (distance > 5) {
                widget.removeClass('cursor-grabbing cursor-pointer').addClass('cursor-move');
            }
            
            let finalX = initialLeft + dx;
            let finalY = initialTop + dy;
            
            const ww = window.innerWidth;
            const wh = window.innerHeight;
            const wWidth = container.outerWidth();
            const wHeight = container.outerHeight();
            
            if (finalX < 10) finalX = 10;
            if (finalY < 10) finalY = 10;
            if (finalX > ww - wWidth - 30) finalX = ww - wWidth - 30;
            if (finalY > wh - wHeight - 10) finalY = wh - wHeight - 10;
            
            container.css({
                left: finalX + 'px',
                top: finalY + 'px',
                right: 'auto',
                bottom: 'auto'
            });
            
            // Динамическое перемещение кнопки стоп, если мало места слева
            updateStopButtonPosition(finalX, wWidth);
        } else if (dragElement === 'panel') {
            // Панель следует за мышкой в 2D (и по X, и по Y)
            let newY = initialTop + dy;
            if (newY > 0) newY = 0; // Не даем провалиться ниже нижнего края
            panel.css('transform', `translate(calc(-50% + ${dx}px), ${newY}px)`);
        }
    });
    
    // --- ОТПУСКАНИЕ (POINTER UP) ---
    $(document).on('pointerup pointercancel', function(e) {
        if (!isDragging) return;
        
        isDragging = false;
        $('body').removeClass('select-none');
        
        let clientX = e.originalEvent.clientX || e.clientX;
        let clientY = e.originalEvent.clientY || e.clientY;
        let dx = clientX - startX;
        let dy = clientY - startY;
        
        if (dragElement === 'widget') {
            const pointerId = e.pointerId || (e.originalEvent && e.originalEvent.pointerId);
            if (pointerId !== undefined) {
                container[0].releasePointerCapture(pointerId);
            }
            
            // Сброс курсора
            widget.removeClass('cursor-grabbing cursor-move').addClass('cursor-pointer');
            
            // 1. SMART DRAG (Проверка на клик)
            const distance = Math.sqrt(dx * dx + dy * dy);
            if (distance < 5) {
                // Это был клик!
                globalTogglePause();
            } else {
                // 2. DOCKING (Проверка близости к нижнему краю)
                if (clientY > window.innerHeight - 60) {
                    showTimerPanel();
                } else {
                    const pos = container[0].getBoundingClientRect();
                    localStorage.setItem('widgetPos', JSON.stringify({top: pos.top, left: pos.left}));
                }
            }
        } else if (dragElement === 'panel') {
            const pointerId = e.pointerId || (e.originalEvent && e.originalEvent.pointerId);
            if (pointerId !== undefined) {
                panel[0].releasePointerCapture(pointerId);
            }
            
            panel.addClass('transition-transform duration-300');
            
            // Если потянули панель в любую сторону достаточно сильно - отрываем её
            const distance = Math.sqrt(dx * dx + dy * dy);
            if (distance > 50) {
                // Вычисляем позицию для новой кнопки так, чтобы она появилась точно под мышкой
                let finalX = clientX - container.outerWidth() / 2;
                let finalY = clientY - container.outerHeight() / 2;
                
                // Защита от выноса виджета за пределы экрана
                const ww = window.innerWidth;
                const wh = window.innerHeight;
                const wWidth = container.outerWidth() || 150;
                const wHeight = container.outerHeight() || 50;
                
                if (finalX < 10) finalX = 10;
                if (finalY < 10) finalY = 10;
                if (finalX > ww - wWidth - 30) finalX = ww - wWidth - 30;
                if (finalY > wh - wHeight - 10) finalY = wh - wHeight - 10;
                
                container.css({ left: finalX + 'px', top: finalY + 'px', right: 'auto', bottom: 'auto' });
                localStorage.setItem('widgetPos', JSON.stringify({top: finalY, left: finalX}));
                updateStopButtonPosition(finalX, wWidth);
                
                panel.css('transform', '');
                hideTimerPanel();
            } else {
                // Возвращаем панель на место
                panel.css('transform', '');
            }
        }
        
        dragElement = null;
    });
});

// --- GLOBAL ADD MODAL ---
/**
 * Открывает глобальное модальное окно для создания новой задачи.
 */
function openGlobalAddModal() {
    $('#globalAddModal').removeClass('hidden');
    // Фокус на поле ввода через небольшую задержку для корректного отображения
    setTimeout(() => $('#globalAddModal input[name="title"]').focus(), 100);
}

/**
 * Закрывает глобальное модальное окно добавления.
 */
function closeGlobalAddModal() {
    $('#globalAddModal').addClass('hidden');
}

/**
 * Автоматически подставляет почасовую ставку (rate) клиента при его выборе в селекте.
 * @param {HTMLSelectElement} selectElem Элемент выбора клиента
 */
function updateRateGlobal(selectElem) {
    const val = $(selectElem).val();
    const row = $(selectElem).closest('form');
    
    if (val === 'new') {
        $('#newCustomerFields').removeClass('hidden');
        $('#globalAddIsFixed').parent().find('.customer-select').removeClass('flex-1').addClass('w-full sm:w-1/3');
    } else {
        $('#newCustomerFields').addClass('hidden');
        $('#globalAddIsFixed').parent().find('.customer-select').addClass('flex-1').removeClass('w-full sm:w-1/3');
    }
}

// --- GLOBAL SPA AJAX NAVIGATION ---
$(document).ready(function() {
    // Перехватываем клики по всем ссылкам для SPA-навигации
    $(document).on('click', 'a', function(e) {
        const href = $(this).attr('href');
        
        // Пропускаем невалидные ссылки, якоря и javascript:
        if (!href || href === '#' || href.startsWith('javascript:')) return;
        
        // Пропускаем ссылки, открывающиеся в новом окне
        if ($(this).attr('target') === '_blank') return;
        
        // Пропускаем ссылки на админку и авторизацию
        if (href.indexOf('/admin') !== -1 || href.indexOf('/auth') !== -1) return;
        
        // Проверяем, что ссылка ведет на наш домен
        if (href.startsWith('http') && !href.startsWith(window.location.origin)) return;

        e.preventDefault();
        loadAjaxPage(href);
    });

    // Обработка кнопок "Назад" и "Вперед" в браузере
    $(window).on('popstate', function(e) {
        loadAjaxPage(window.location.href, false);
    });
});

/**
 * Загружает содержимое страницы через AJAX и вставляет его в #main-content.
 * @param {string} url URL для загрузки
 * @param {boolean} push Нужно ли сохранять URL в историю браузера (по умолчанию true)
 */
function loadAjaxPage(url, push = true) {
    // Показываем индикатор загрузки (затемняем контент)
    $('#main-content').css('opacity', '0.5');
    
    $.ajax({
        url: url,
        type: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }, // Явно указываем AJAX
        success: function(html) {
            // Обновляем контент
            $('#main-content').html(html);
            $('#main-content').css('opacity', '1');
            
            // Обновляем URL в строке браузера
            if (push) {
                window.history.pushState(null, '', url);
            }
            
            // Обновляем визуальное состояние меню (подсветка активного раздела)
            updateActiveMenu(url);
            
            // Прокручиваем наверх страницы
            window.scrollTo(0, 0);
        },
        error: function() {
            // Фолбэк: если что-то пошло не так, просто переходим по ссылке как обычно
            window.location.href = url;
        }
    });
}

/**
 * Обновляет классы в навигационном меню, чтобы выделить текущий активный раздел.
 * @param {string} url Текущий URL страницы
 */
function updateActiveMenu(url) {
    // Снимаем выделение со всех ссылок в навбаре
    $('nav a.transition-all').removeClass('opacity-100 nav-cloud-active').addClass('opacity-70 hover:opacity-100');
    
    // Ищем подходящую ссылку и подсвечиваем её
    $('nav a.transition-all').each(function() {
        const linkHref = $(this).attr('href');
        if (!linkHref) return;
        
        // Если ссылка - это корень (dashboard)
        if (url === window.location.origin + '/' || url === window.location.origin) {
            if (linkHref === window.location.origin + '/' || linkHref === window.location.origin + '/tasks') {
                $(this).removeClass('opacity-70 hover:opacity-100').addClass('opacity-100 nav-cloud-active');
                window.isDashboardPage = true; // Обновляем глобальный флаг
            }
        } 
        // Если другая страница
        else if (url.indexOf(linkHref) !== -1 && linkHref !== window.location.origin + '/') {
            $(this).removeClass('opacity-70 hover:opacity-100').addClass('opacity-100 nav-cloud-active');
            if (linkHref.indexOf('/tasks') !== -1) {
                window.isDashboardPage = true;
            } else {
                window.isDashboardPage = false;
            }
        }
    });
    
    // После загрузки страницы обновляем видимость UI таймера в зависимости от страницы
    if (typeof showTimerUI === 'function' && typeof hideTimerUI === 'function') {
        if (globalTimerInterval || isPaused) {
            showTimerUI();
        } else {
            hideTimerUI();
        }
    }
}

// --- AUTH & PROFILE FORMS ---
$(document).on('submit', '#register-form', function(e) {
    e.preventDefault();
    let btn = $(this).find('button[type="submit"]');
    let spinner = btn.find('.spinner');
    let errorBox = $('#register-errors');
    
    btn.prop('disabled', true).addClass('opacity-75 cursor-not-allowed');
    spinner.removeClass('hidden');
    errorBox.addClass('hidden').html('');

    $.post($(this).attr('action'), $(this).serialize(), function(response) {
        try {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                loadAjaxPage('/', false); // Перенаправляем на главную
            } else {
                errorBox.removeClass('hidden').html(res.message);
                btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
                spinner.addClass('hidden');
            }
        } catch (err) {
            errorBox.removeClass('hidden').html("Произошла системная ошибка.");
            btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
            spinner.addClass('hidden');
        }
    });
});

$(document).on('submit', '#profile-form', function(e) {
    e.preventDefault();
    let btn = $(this).find('button[type="submit"]');
    let spinner = btn.find('.spinner');
    let errorBox = $('#profile-errors');
    
    btn.prop('disabled', true).addClass('opacity-75 cursor-not-allowed');
    spinner.removeClass('hidden');
    errorBox.addClass('hidden').html('');

    $.post($(this).attr('action'), $(this).serialize(), function(response) {
        try {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                loadAjaxPage(window.location.href, false); // Перезагружаем профиль
            } else {
                errorBox.removeClass('hidden').html(res.message);
                btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
                spinner.addClass('hidden');
            }
        } catch (err) {
            errorBox.removeClass('hidden').html("Произошла системная ошибка.");
            btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
            spinner.addClass('hidden');
        }
    });
});

// =========================================
// ДИНАМИЧЕСКИЕ ТЕМЫ (AJAX ПЕРЕКЛЮЧЕНИЕ)
// =========================================
$(document).on('click', '.theme-selector', function(e) {
    e.preventDefault();
    
    // Получаем саму нажатую кнопку-кружочек
    let btn = $(this);
    
    // Получаем название выбранной темы из атрибута data-theme (например, 'theme-emerald')
    let themeName = btn.data('theme');
    
    // Делаем AJAX POST-запрос. Используем базовый путь из формы профиля (если есть) 
    // или строим относительный путь через index.php, чтобы избежать 500 ошибки.
    // Получаем текущую прозрачность из ползунка, если он есть на странице
    let opacity = $('#theme_opacity').length ? $('#theme_opacity').val() : '1.00';
    let hue = $('#theme_hue').length ? $('#theme_hue').val() : '221';
    
    let ajaxUrl = window.location.origin + '/index.php/profile/save_theme_ajax';
    
    $.post(ajaxUrl, { theme: themeName, opacity: opacity, hue: hue }, function(response) {
        try {
            let res = JSON.parse(response);
            
            // Если бэкенд успешно сохранил тему в базу
            if (res.status === 'success') {
                // 1. Мгновенно меняем тему на фронтенде без перезагрузки:
                // Удаляем все старые классы, начинающиеся с "theme-" у тега <body>
                $('body').removeClass(function (index, className) {
                    return (className.match(/(^|\s)theme-\S+/g) || []).join(' ');
                });
                
                // И добавляем новый класс темы, чтобы CSS перекрасил интерфейс
                $('body').addClass(themeName);
                
                // Управляем видимостью ползунка Свой цвет и инлайн стилями
                if (themeName === 'theme-custom') {
                    $('#custom_hue_container').removeClass('hidden');
                    document.body.style.setProperty('--theme-h', hue);
                } else {
                    $('#custom_hue_container').addClass('hidden');
                    document.body.style.removeProperty('--theme-h');
                }
                
                // 2. Обновляем визуальное состояние кнопок (выделяем активную)
                // Сначала убираем обводку активности у всех кружочков
                $('.theme-selector').each(function() {
                    $(this).removeClass('border-gray-800 ring-4').addClass('border-transparent hover:scale-110');
                    $(this).css('box-shadow', 'none'); // удаляем inline-свечение
                });
                
                // Добавляем обводку активности только для нажатого кружочка (чтобы он был выделен)
                let ringHex = '#60a5fa';
                if(themeName === 'theme-emerald') ringHex = '#34d399';
                else if(themeName === 'theme-sunset') ringHex = '#fb923c';
                else if(themeName === 'theme-berry') ringHex = '#fb7185';
                else if(themeName === 'theme-night') ringHex = '#94a3b8';
                else if(themeName === 'theme-ocean') ringHex = '#22d3ee';
                else if(themeName === 'theme-lavender') ringHex = '#c084fc';
                else if(themeName === 'theme-coffee') ringHex = '#fbbf24';
                else if(themeName === 'theme-custom') ringHex = '#a855f7';
                
                btn.removeClass('border-transparent hover:scale-110')
                   .addClass('border-gray-800 ring-4')
                   .css('box-shadow', '0 0 0 4px ' + ringHex);
                   
            } else {
                alert("Ошибка: " + res.message);
            }
        } catch (err) {
            console.error("System Error: ", err);
            alert("Произошла системная ошибка при сохранении темы.");
        }
    });
});

// Обработчик ползунка прозрачности темы (движение)
$(document).on('input', '#theme_opacity', function() {
    // Считываем значение от 0.05 до 1.00
    let opacity = parseFloat($(this).val());
    
    // Обновляем бейджик с процентами (умножаем на 100 и округляем)
    $('#opacity_value').text(Math.round(opacity * 100) + '%');
    
    // Мгновенно применяем прозрачность через CSS переменную к body (а не к documentElement, чтобы перекрыть inline стили из body.php)
    document.body.style.setProperty('--theme-opacity', opacity);
});

// Обработчик ползунка прозрачности темы (отпускание/сохранение)
$(document).on('change', '#theme_opacity', function() {
    let opacity = $(this).val();
    let currentTheme = 'theme-default';
    let hue = $('#theme_hue').length ? $('#theme_hue').val() : '221';
    
    // Ищем, какая тема сейчас активна (у нее есть рамка ring-4)
    $('.theme-selector').each(function() {
        if ($(this).hasClass('ring-4')) {
            currentTheme = $(this).data('theme');
        }
    });
    
    let ajaxUrl = window.location.origin + '/index.php/profile/save_theme_ajax';
    
    // Отправляем на сервер активную тему и новую прозрачность
    $.post(ajaxUrl, { theme: currentTheme, opacity: opacity, hue: hue }, function(response) {
        try {
            let res = JSON.parse(response);
            if (res.status !== 'success') {
                alert("Ошибка сохранения прозрачности: " + res.message);
            }
        } catch (err) {
            console.error("System Error: ", err);
        }
    });
});

// Обработчик ползунка тона (движение)
$(document).on('input', '#theme_hue', function() {
    let hue = $(this).val();
    $('#hue_value').html(hue + '&deg;');
    document.body.style.setProperty('--theme-h', hue);
});

// Обработчик ползунка тона (отпускание/сохранение)
$(document).on('change', '#theme_hue', function() {
    let hue = $(this).val();
    let opacity = $('#theme_opacity').length ? $('#theme_opacity').val() : '1.00';
    let currentTheme = 'theme-custom';
    
    let ajaxUrl = window.location.origin + '/index.php/profile/save_theme_ajax';
    
    $.post(ajaxUrl, { theme: currentTheme, opacity: opacity, hue: hue }, function(response) {
        try {
            let res = JSON.parse(response);
            if (res.status !== 'success') {
                alert("Ошибка сохранения цвета: " + res.message);
            }
        } catch (err) {
            console.error("System Error: ", err);
        }
    });
});

// --- Глобальное плавающее модальное окно тем оформления ---

function openThemeModal() {
    $('#themeSettingsModal').removeClass('hidden');
    setTimeout(() => {
        $('#themeSettingsModal').removeClass('opacity-0');
    }, 10);
}

function closeThemeModal() {
    $('#themeSettingsModal').addClass('opacity-0');
    setTimeout(() => {
        $('#themeSettingsModal').addClass('hidden');
    }, 200);
}

// Drag and drop логика для модального окна
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('themeSettingsModal');
    const header = document.getElementById('themeSettingsModalHeader');
    
    if (!modal || !header) return;

    let isDragging = false;
    let currentX;
    let currentY;
    let initialX;
    let initialY;
    let xOffset = 0;
    let yOffset = 0;

    header.addEventListener('mousedown', dragStart);
    document.addEventListener('mousemove', drag);
    document.addEventListener('mouseup', dragEnd);

    // Поддержка Touch
    header.addEventListener('touchstart', dragStart, {passive: false});
    document.addEventListener('touchmove', drag, {passive: false});
    document.addEventListener('touchend', dragEnd);

    function dragStart(e) {
        if (e.type === "touchstart") {
            initialX = e.touches[0].clientX - xOffset;
            initialY = e.touches[0].clientY - yOffset;
        } else {
            initialX = e.clientX - xOffset;
            initialY = e.clientY - yOffset;
        }
        
        // Проверяем что кликнули именно по шапке, а не по кнопке закрытия
        if (e.target.closest('button')) return;
        
        isDragging = true;
        modal.classList.add('dragging-theme-modal');
    }

    function drag(e) {
        if (isDragging) {
            if (e.type === "touchmove") {
                // e.preventDefault(); можно добавить для предотвращения скролла
                currentX = e.touches[0].clientX - initialX;
                currentY = e.touches[0].clientY - initialY;
            } else {
                currentX = e.clientX - initialX;
                currentY = e.clientY - initialY;
            }

            xOffset = currentX;
            yOffset = currentY;

            setTranslate(currentX, currentY, modal);
        }
    }

    function setTranslate(xPos, yPos, el) {
        el.style.transform = `translate3d(${xPos}px, ${yPos}px, 0)`;
    }

    function dragEnd(e) {
        initialX = currentX;
        initialY = currentY;
        isDragging = false;
        modal.classList.remove('dragging-theme-modal');
    }
});

// =========================================
// ОБРАБОТЧИКИ КОРЗИНЫ (TRASH)
// =========================================

// Обработчик восстановления из корзины (SPA, делегирование)
$(document).off('click', '.restore-trash-btn').on('click', '.restore-trash-btn', function() {
    var taskId = $(this).data('task-id');
    
    // Запрашиваем подтверждение
    if (!confirm(window.globalLang.js_confirm_restore)) {
        return;
    }
    
    // Блокируем кнопку на время запроса
    var btn = $(this);
    btn.prop('disabled', true).addClass('opacity-50');
    
    $.ajax({
        url: window.globalApi.restore_trash,
        method: 'POST',
        data: { task_id: taskId },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Перезагружаем текущую страницу корзины через наш SPA загрузчик
                loadAjaxPage(window.location.href);
            } else {
                alert(response.message || 'Ошибка восстановления');
                btn.prop('disabled', false).removeClass('opacity-50');
            }
        },
        error: function() {
            alert('Ошибка сети при восстановлении');
            btn.prop('disabled', false).removeClass('opacity-50');
        }
    });
});

// Обработчик полного (безвозвратного) удаления (SPA, делегирование)
$(document).off('click', '.hard-delete-btn').on('click', '.hard-delete-btn', function() {
    var taskId = $(this).data('task-id');
    
    if (!confirm(window.globalLang.js_confirm_hard_delete)) {
        return;
    }
    
    var btn = $(this);
    btn.prop('disabled', true).addClass('opacity-50');
    
    $.ajax({
        url: window.globalApi.hard_delete,
        method: 'POST',
        data: { task_id: taskId },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                loadAjaxPage(window.location.href);
            } else {
                alert(response.message || 'Ошибка удаления');
                btn.prop('disabled', false).removeClass('opacity-50');
            }
        },
        error: function() {
            alert('Ошибка сети при удалении');
            btn.prop('disabled', false).removeClass('opacity-50');
        }
    });
});

// Обработчик изменения выбора клиента для подгрузки ТЗ
$(document).on('change', '.customer-select', function() {
    var customerId = $(this).val();
    var form = $(this).closest('form, #editTaskModal');
    var specSelect = form.find('.spec-select');
    var specContainer = form.find('.spec-container, #globalAddSpecContainer, #editTaskSpecContainer');
    
    if (!customerId || customerId === 'new') {
        specSelect.html('<option value="">Связать с ТЗ...</option>');
        specContainer.addClass('hidden');
        return;
    }
    
    // AJAX-запрос списка ТЗ
    $.getJSON(window.location.origin + '/index.php/customers/get_specs_ajax/' + customerId, function(res) {
        if (res.status === 'success') {
            var html = '<option value="">Связать с ТЗ...</option>';
            if (res.data && res.data.length > 0) {
                res.data.forEach(function(spec) {
                    html += '<option value="' + spec.id + '">' + spec.title + '</option>';
                });
                specSelect.html(html);
                specContainer.removeClass('hidden');
                
                // Если у нас сохранен ранее выбранный specId, выбираем его
                var savedSpecId = specSelect.data('pending-select');
                if (savedSpecId) {
                    specSelect.val(savedSpecId);
                    specSelect.removeData('pending-select');
                }
            } else {
                specSelect.html('<option value="">Нет созданных ТЗ</option>');
                specContainer.addClass('hidden');
            }
        } else {
            specSelect.html('<option value="">Ошибка загрузки ТЗ</option>');
            specContainer.addClass('hidden');
        }
    });
});

// =========================================
// ОБРАБОТЧИКИ МОДУЛЯ СТАТИСТИКИ (AJAX SPA)
// =========================================

/**
 * Вспомогательная функция для форматирования JS объекта даты в строку формата YYYY-MM-DD.
 * @param {Date} date Объект даты
 * @returns {string} Строка в формате YYYY-MM-DD
 */
function formatJsDateForStats(date) {
    // Получаем год из объекта даты
    const year = date.getFullYear();
    // Получаем месяц и увеличиваем на 1 (так как месяцы в JS 0-индексированы), дополняем нулем слева
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    // Получаем число месяца и дополняем нулем слева
    const day = date.getDate().toString().padStart(2, '0');
    // Возвращаем склеенную через дефис строку
    return `${year}-${month}-${day}`;
}

/**
 * Функция сбора параметров фильтров и мгновенного обновления контента страницы статистики по AJAX.
 * Строит URL с GET параметрами и загружает его через существующий SPA метод loadAjaxPage.
 */
function refreshStatistics() {
    // Получаем состояние чекбокса: 1 если выбран (показывать архив), 0 если снят (скрыть архив)
    const showArchived = $('#filter-show-archived').is(':checked') ? 1 : 0;
    
    // Инициализируем массив для хранения GET параметров запроса
    const params = [];
    
    // Всегда добавляем флаг архивных в параметры запроса
    params.push('show_archived=' + showArchived);
    
    // Если на странице есть поле ввода даты начала, считываем его
    if ($('#stat-date-start').length) {
        // Добавляем параметр start в массив
        params.push('start=' + $('#stat-date-start').val());
    }
    
    // Если на странице есть поле ввода даты окончания, считываем его
    if ($('#stat-date-end').length) {
        // Добавляем параметр end в массив
        params.push('end=' + $('#stat-date-end').val());
    }

    // Собираем множественные фильтры заказчиков
    $('input[name="customer_filters[]"]:checked').each(function() {
        params.push('customer_filters[]=' + encodeURIComponent($(this).val()));
    });

    // Собираем множественные фильтры калькуляций
    $('input[name="calculation_filters[]"]:checked').each(function() {
        params.push('calculation_filters[]=' + encodeURIComponent($(this).val()));
    });

    // Собираем множественные фильтры ТЗ
    $('input[name="spec_filters[]"]:checked').each(function() {
        params.push('spec_filters[]=' + encodeURIComponent($(this).val()));
    });

    // Если на странице присутствует выпадающий список сортировки
    if ($('#filter-sort').length) {
        // Добавляем тип сортировки в параметры GET-запроса
        params.push('sort_by=' + $('#filter-sort').val());
    }

    // Если на странице есть скрытое поле направления сортировки
    if ($('#filter-sort-dir').length) {
        // Добавляем направление в параметры запроса
        params.push('sort_dir=' + $('#filter-sort-dir').val());
    }
    
    // Склеиваем параметры через амперсанд и прикрепляем к текущему пути URL (pathname)
    const targetUrl = window.location.pathname + '?' + params.join('&');
    
    // Вызываем стандартный SPA-метод загрузки страницы без перезагрузки
    loadAjaxPage(targetUrl, true);
}

// Обработчик переключения чекбокса "Показывать архивные"
$(document).on('change', '#filter-show-archived', function() {
    // Вызываем AJAX-обновление статистики при изменении состояния чекбокса
    refreshStatistics();
});

// Обработчик изменения чекбоксов фильтрации заказчиков, калькуляций и ТЗ
$(document).on('change', 'input[name="customer_filters[]"], input[name="calculation_filters[]"], input[name="spec_filters[]"]', function() {
    // Вызываем AJAX-обновление статистики при изменении любого фильтра
    refreshStatistics();
});

// Обработчик изменения типа сортировки
$(document).on('change', '#filter-sort', function() {
    // Вызываем AJAX-обновление статистики при изменении сортировки
    refreshStatistics();
});

// Обработчик изменения ручных полей ввода календарей (даты начала или конца)
$(document).on('change', '#stat-date-start, #stat-date-end', function() {
    // Перезагружаем статистику с новыми датами
    refreshStatistics();
});

// Обработчик клика по крестику на цветной плашке (чипсе) скрытия архивных
$(document).on('click', '#chip-hide-archived', function() {
    // Принудительно устанавливаем чекбокс в дефолтное состояние (checked = true)
    $('#filter-show-archived').prop('checked', true);
    // Мгновенно перезагружаем статистику для сброса фильтра
    refreshStatistics();
});

// Обработчик клика по чипсу сброса конкретного заказчика
$(document).on('click', '.chip-customer-item', function() {
    const val = $(this).data('value');
    // Снимаем отметку с чекбокса соответствующего заказчика
    $('input[name="customer_filters[]"][value="' + val + '"]').prop('checked', false);
    // Перезагружаем статистику
    refreshStatistics();
});

// Обработчик клика по чипсу сброса конкретной калькуляции
$(document).on('click', '.chip-calculation-item', function() {
    const val = $(this).data('value');
    // Снимаем отметку с чекбокса соответствующей калькуляции
    $('input[name="calculation_filters[]"][value="' + val + '"]').prop('checked', false);
    // Перезагружаем статистику
    refreshStatistics();
});

// Обработчик клика по чипсу сброса конкретного ТЗ
$(document).on('click', '.chip-spec-item', function() {
    const val = $(this).data('value');
    // Снимаем отметку с чекбокса соответствующего ТЗ
    $('input[name="spec_filters[]"][value="' + val + '"]').prop('checked', false);
    // Перезагружаем статистику
    refreshStatistics();
});

// Обработчик клика по кнопке изменения направления сортировки
$(document).on('click', '#btn-toggle-sort-dir', function(e) {
    // Отменяем дефолтное действие
    e.preventDefault();
    // Получаем текущее направление
    const currentDir = $('#filter-sort-dir').val() || 'desc';
    // Меняем его на противоположное
    const newDir = (currentDir === 'asc') ? 'desc' : 'asc';
    
    // Записываем в инпут
    $('#filter-sort-dir').val(newDir);
    
    // Визуально разворачиваем иконку на 180 градусов для 'asc'
    if (newDir === 'asc') {
        $(this).addClass('transform rotate-180');
    } else {
        $(this).removeClass('transform rotate-180');
    }
    
    // Запускаем AJAX обновление статистики
    refreshStatistics();
});


// Обработчик клика по быстрым кнопкам дат (Сегодня, Вчера, Неделя, Месяц)
$(document).on('click', '.btn-fast-date', function(e) {
    // Предотвращаем стандартное поведение кнопки
    e.preventDefault();
    
    // Считываем тип выбранного периода из дата-атрибута кнопки
    const range = $(this).data('range');
    
    // Получаем текущую дату и время на клиенте
    const now = new Date();
    // Получаем текущий час
    const hour = now.getHours();
    
    // Инициализируем базовую дату. Если сейчас меньше 5 утра, то текущий рабочий день начался вчера.
    let baseDate = new Date();
    // Проверяем условие времени до 5 утра
    if (hour < 5) {
        // Устанавливаем базовый день на вчера
        baseDate.setDate(baseDate.getDate() - 1);
    }
    
    // Создаем копии объектов дат для начала и конца интервала
    let start = new Date(baseDate);
    let end = new Date(baseDate);

    // Вычисляем границы дат для каждого быстрого фильтра
    if (range === 'today') {
        // Сегодня: интервал равен одному базовому рабочему дню
    } else if (range === 'yesterday') {
        // Вчера: сдвигаем старт и конец на 1 день назад от базового дня
        start.setDate(baseDate.getDate() - 1);
        end.setDate(baseDate.getDate() - 1);
    } else if (range === 'week') {
        // Неделя: определяем день недели для базового дня (1 - Понедельник, 7 - Воскресенье)
        const day = baseDate.getDay() || 7;
        // Перемещаем старт на понедельник текущей рабочей недели
        start.setDate(baseDate.getDate() - (day - 1));
        // Конец интервала — текущий базовый день
    } else if (range === 'month') {
        // Месяц: переводим старт на первое число текущего рабочего месяца
        start.setDate(1);
        // Конец — текущий базовый рабочий день
    }

    // Подставляем вычисленные и отформатированные даты в соответствующие поля на форме
    $('#stat-date-start').val(formatJsDateForStats(start));
    $('#stat-date-end').val(formatJsDateForStats(end));
    
    // Запускаем AJAX-обновление статистики с новым выбранным периодом
    refreshStatistics();
});

// Обработчик плавного раскрытия подразделов дерева задач (аккордеон) в проектном срезе
$(document).on('click', '.toggle-stats-children', function(e) {
    // Находим ближайший родительский элемент LI дерева задач
    const li = $(this).closest('li');
    
    // Извлекаем вложенный список детей (stats-children), относящийся именно к этому LI
    const childrenUl = li.children('.stats-children');
    
    // Находим иконку раскрытия (стрелочку), расположенную внутри текущего заголовка
    const arrowIcon = $(this).find('.icon-stats-expand');
    
    // Плавно разворачиваем или сворачиваем вложенный список за 200мс
    childrenUl.slideToggle(200);
    
    // Поворачиваем стрелочку иконки на 180 градусов
    arrowIcon.toggleClass('rotate-180');
});

