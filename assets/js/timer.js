// --- ГЛОБАЛЬНАЯ ЛОГИКА ТАЙМЕРА И ИНТЕРФЕЙСА СЕКУНДОМЕРА ---

// Интервал обновления отображения таймера (каждую секунду)
let globalTimerInterval = null;
// Интервал мигания заголовка вкладки при паузе
let globalTitleInterval = null;
// Локальное время запуска таймера (время на клиенте)
let localStartTime = null;
// Начальное количество секунд текущей сессии на момент загрузки страницы
let initialSessionSeconds = 0;
// Суммарное накопленное время задачи за предыдущие сессии (в секундах)
let totalAccumulatedSeconds = 0;
// Название текущей активной задачи
let currentTaskTitle = "";
// ID текущей активной задачи
let currentTaskId = null;
// Флаг нахождения таймера на паузе
let isPaused = false;
// Состояние мигания (для чередования текста заголовка при паузе)
let blinkState = false;

/**
 * Форматирует число секунд в строку времени вида HH:MM:SS
 * @param {number} totalSeconds - Общее количество секунд
 * @returns {string} Форматированное время
 */
function formatTime(totalSeconds) {
    const h = Math.floor(totalSeconds / 3600).toString().padStart(2, '0');
    const m = Math.floor((totalSeconds % 3600) / 60).toString().padStart(2, '0');
    const s = (totalSeconds % 60).toString().padStart(2, '0');
    return `${h}:${m}:${s}`;
}

/**
 * Генерирует favicon в виде цветного круга с использованием Canvas
 * @param {string} color - Hex-код цвета
 * @returns {string} Data URL сгенерированного изображения
 */
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

// Зеленый фавикон для активного состояния таймера
const faviconGreen = createCircleFavicon('#10B981');
// Красный фавикон для состояния паузы
const faviconRed = createCircleFavicon('#EF4444');
// Стандартный фавикон по умолчанию (эмодзи секундомера)
const faviconDefault = 'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⏱️</text></svg>';

/**
 * Устанавливает favicon страницы на переданный URL/DataURI
 * @param {string} url - Ссылка на изображение
 */
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
 * Вычисляет реальное прошедшее время и обновляет все дисплеи таймера на странице
 */
function updateGlobalTimerDisplay() {
    if (localStartTime === null) return;
    
    // Вычисляем разницу во времени между текущим моментом и моментом старта
    const elapsedSinceLoad = Math.floor((Date.now() - localStartTime) / 1000);
    const currentSessionSeconds = initialSessionSeconds + elapsedSinceLoad;
    
    if (currentSessionSeconds >= 0) {
        const timeStr = formatTime(currentSessionSeconds);
        const totalStr = formatTime(totalAccumulatedSeconds + currentSessionSeconds);
        
        // Обновляем текст в нижней панели таймера
        $('#timerDisplay').text(timeStr);
        $('#totalTimerDisplay').text(totalStr);
        
        // Обновляем текст в плавающем круглом виджете
        $('#globalWidgetTimer').text(timeStr);
        
        // Если таймер не на паузе, обновляем заголовок страницы и иконку
        if (!isPaused) {
            document.title = `🟢 ${timeStr} - ${currentTaskTitle}`;
            setFavicon(faviconGreen);
        }
    }
}

/**
 * Мигает заголовком страницы (чередует текст ПАУЗА / время) при приостановленном таймере
 */
function blinkPausedTitle() {
    if (!isPaused) return;
    const timeStr = formatTime(initialSessionSeconds); // Время заморожено
    blinkState = !blinkState;
    document.title = blinkState ? `🔴 ПАУЗА - ${currentTaskTitle}` : `🔴 ${timeStr} - ${currentTaskTitle}`;
    setFavicon(blinkState ? faviconRed : faviconDefault);
}

/**
 * Инициализирует глобальный таймер при загрузке или AJAX-смене страниц
 */
function initGlobalTimer() {
    // Очищаем старые интервалы
    if (globalTimerInterval) clearInterval(globalTimerInterval);
    if (globalTitleInterval) clearInterval(globalTitleInterval);
    
    // Очищаем неиспользуемый ключ localStorage
    localStorage.removeItem('pausedTimerInfo');

    // Если на сервере есть активная сессия времени
    if (window.globalActiveSession) {
        isPaused = (window.globalActiveSession.is_paused == 1);
        currentTaskTitle = window.globalActiveSession.task_title;
        currentTaskId = window.globalActiveSession.task_id;
        initialSessionSeconds = parseInt(window.globalActiveSession.current_elapsed) || 0;
        totalAccumulatedSeconds = parseInt(window.globalActiveSession.total_accumulated) || 0;
        localStartTime = Date.now();
        
        // Показываем элементы управления таймером
        showTimerUI();
        
        // Запускаем интервал мигания при паузе или интервал отсчета при работе
        if (isPaused) {
            globalTitleInterval = setInterval(blinkPausedTitle, 1000);
            blinkPausedTitle();
        } else {
            updateGlobalTimerDisplay();
            globalTimerInterval = setInterval(updateGlobalTimerDisplay, 1000);
            setFavicon(faviconGreen);
        }
    } else {
        // Если активной сессии нет, сбрасываем заголовки и прячем панели
        isPaused = false;
        hideTimerUI();
        document.title = 'Тайм-трекер';
        setFavicon(faviconDefault);
    }
}

/**
 * Отображает и стилизует элементы интерфейса активного таймера
 */
function showTimerUI() {
    // Устанавливаем названия задач в виджете и панели
    $('#globalWidgetTitle').text(currentTaskTitle);
    $('#activeTimerTitle').text(currentTaskTitle);
    
    // Если панель была развернута пользователем, показываем ее, иначе плавающий виджет
    if (window.isTimerPanelOpen) {
        toggleTimerPanelDOM(true);
    } else {
        // Скрываем нижнюю панель
        $('#activeTimerPanel').removeClass('translate-y-0').addClass('translate-y-full');
        
        // Показываем плавающий виджет с анимацией
        $('#globalFloatingWidgetContainer').removeClass('hidden');
        setTimeout(() => {
            $('#globalFloatingWidgetContainer')
                .removeClass('opacity-0 scale-50 pointer-events-none')
                .addClass('opacity-100 scale-100');
        }, 10);
    }
    
    // Настраиваем цвета и иконки в зависимости от состояния паузы
    if (isPaused) {
        $('#timerDisplay').text(formatTime(initialSessionSeconds));
        $('#totalTimerDisplay').text(formatTime(totalAccumulatedSeconds + initialSessionSeconds));
        
        // Кнопка в развернутой панели
        $('#btnPauseDashboard')
            .html(window.globalLang.btn_continue)
            .removeClass('bg-yellow-500 hover:bg-yellow-400')
            .addClass('bg-green-500 hover:bg-green-400');
        
        // Виджет
        $('#globalWidgetTimer').text(formatTime(initialSessionSeconds));
        $('#globalFloatingWidget').addClass('widget-paused-bg animate-pulse');
        
        // Показываем кнопку "Стоп" в виджете
        $('#globalWidgetStopBtn')
            .removeClass('opacity-0 scale-50 pointer-events-none')
            .addClass('opacity-100 scale-100');
            
        $('#globalWidgetIcon').html('⏸');
        
        // Анимируем логотип в шапке (пауза)
        $('#logo-emoji').text('⏸️');
        $('#logo-svg').removeClass('text-white text-green-300').addClass('text-yellow-300 animate-pulse');
        $('#logo-title').removeClass('text-white text-green-300').addClass('text-yellow-300 animate-pulse');
    } else {
        // Кнопка в развернутой панели
        $('#btnPauseDashboard')
            .html(window.globalLang.btn_pause)
            .removeClass('bg-green-500 hover:bg-green-400')
            .addClass('bg-yellow-500 hover:bg-yellow-400');
            
        // Сбрасываем эффекты паузы на виджете
        $('#globalFloatingWidget').removeClass('widget-paused-bg animate-pulse');
        
        // Прячем кнопку "Стоп" в виджете
        $('#globalWidgetStopBtn')
            .removeClass('opacity-100 scale-100')
            .addClass('opacity-0 scale-50 pointer-events-none');
            
        // Анимированная SVG-иконка часового круга
        $('#globalWidgetIcon').html('<svg class="w-5 h-5 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2.5"></circle><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6l3.5 2" class="anim-spin-slow" style="transform-origin: 12px 12px;"></path></svg>');
        
        // Логотип в шапке (активная работа)
        $('#logo-emoji').text('⏱️');
        $('#logo-svg').removeClass('text-white text-yellow-300 animate-pulse').addClass('text-green-300');
        $('#logo-title').removeClass('text-white text-yellow-300 animate-pulse').addClass('text-green-300');
    }
}

/**
 * Скрывает все элементы управления таймером (когда таймер полностью остановлен)
 */
function hideTimerUI() {
    $('#activeTimerPanel').removeClass('translate-y-0').addClass('translate-y-full');
    
    // Возвращаем логотип в неактивное состояние по умолчанию
    $('#logo-emoji').text('💼');
    $('#logo-svg').removeClass('text-green-300 text-yellow-300 animate-pulse').addClass('text-white');
    $('#logo-title').removeClass('text-green-300 text-yellow-300 animate-pulse').addClass('text-white');
    
    // Прячем виджет с анимацией исчезновения
    $('#globalFloatingWidgetContainer')
        .removeClass('opacity-100 scale-100')
        .addClass('opacity-0 scale-50 pointer-events-none');
        
    setTimeout(() => {
        $('#globalFloatingWidgetContainer').addClass('hidden');
    }, 500);
}

/**
 * Переключает видимость панели и виджета в DOM (без анимации)
 * @param {boolean} show - Если true, развернуть панель, иначе виджет
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
 * Разворачивает панель таймера с анимацией View Transitions (при поддержке)
 */
window.showTimerPanel = function() {
    window.isTimerPanelOpen = true;
    if (document.startViewTransition) {
        $('#globalFloatingWidgetContainer').css('view-transition-name', 'timer-morph');
        $('#activeTimerPanel').css('view-transition-name', 'none').css('transition', 'none');
        
        document.startViewTransition(() => {
            toggleTimerPanelDOM(true);
            $('#globalFloatingWidgetContainer').css('view-transition-name', 'none');
            $('#activeTimerPanel').css('view-transition-name', 'timer-morph');
        });
    } else {
        toggleTimerPanelDOM(true);
    }
};

/**
 * Сворачивает панель таймера в виджет с анимацией View Transitions (при поддержке)
 */
window.hideTimerPanel = function() {
    window.isTimerPanelOpen = false;
    if (document.startViewTransition) {
        $('#activeTimerPanel').css('view-transition-name', 'timer-morph').css('transition', 'none');
        $('#globalFloatingWidgetContainer').css('view-transition-name', 'none');
        
        document.startViewTransition(() => {
            toggleTimerPanelDOM(false);
            $('#activeTimerPanel').css('view-transition-name', 'none');
            $('#globalFloatingWidgetContainer').css('view-transition-name', 'timer-morph');
        });
    } else {
        toggleTimerPanelDOM(false);
    }
};

/**
 * Переключает состояние паузы (активирует/приостанавливает сессию) через AJAX
 */
function actionTogglePause() {
    if (!currentTaskId) return;
    
    if (isPaused) {
        // Продолжить таймер
        $.post(window.globalApi.start, { task_id: currentTaskId }, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                window.globalActiveSession = res.data;
                initGlobalTimer();
                loadAjaxPage(window.location.href, false); // Перезагружаем SPA страницу
            } else {
                alert(res.message);
            }
        });
    } else {
        // Поставить на паузу
        $.post(window.globalApi.pause, {}, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                window.globalActiveSession = res.data;
                initGlobalTimer();
                loadAjaxPage(window.location.href, false); // Перезагружаем SPA страницу
            } else {
                alert(res.message);
            }
        });
    }
}

/**
 * Завершает сессию таймера с возможностью сохранения комментария (Note)
 */
function actionStopTimer() {
    const elapsedSinceLoad = Math.floor((Date.now() - localStartTime) / 1000);
    const currentSessionSeconds = isPaused ? initialSessionSeconds : initialSessionSeconds + elapsedSinceLoad;

    let note = "";
    // Запрашиваем комментарий, только если сессия длилась более 1 минуты
    if (currentSessionSeconds >= 60) {
        note = prompt(window.globalLang.js_prompt_stop_timer, "");
        if (note === null) return; // Отмена остановки
    }

    $.post(window.globalApi.stop, { note: note }, function(response) {
        let res = JSON.parse(response);
        if (res.status === 'success' || res.status === 'spam') {
            window.globalActiveSession = null;
            initGlobalTimer();
            loadAjaxPage(window.location.href, false); // Перезагружаем SPA страницу
        } else {
            alert(res.message);
        }
    });
}

/**
 * Корректирует позицию кнопки "Стоп" в зависимости от положения виджета на экране
 * @param {number} x - Позиция виджета по оси X
 * @param {number} wWidth - Ширина виджета
 */
function updateStopButtonPosition(x, wWidth) {
    const btn = $('#globalWidgetStopBtn');
    if (x < 65) {
        btn.css('left', (wWidth + 8) + 'px');
    } else {
        btn.css('left', '-56px');
    }
}

// Глобальные методы-обертки для вызова из HTML-кода
window.globalTogglePause = function() {
    actionTogglePause();
};
window.pauseTimer = function() {
    actionTogglePause();
};
window.stopTimer = function() {
    actionStopTimer();
};

/**
 * Запускает секундомер для выбранной задачи
 * @param {number|string} taskId - Идентификатор запускаемой задачи
 */
window.startTimer = function(taskId) {
    $.post(window.globalApi.start, { task_id: taskId }, function(response) {
        let res = JSON.parse(response);
        if (res.status === 'success') {
            localStorage.removeItem('pausedTimerInfo');
            window.globalActiveSession = res.data;
            initGlobalTimer();
            loadAjaxPage(window.location.href, false); // Обновляем страницу через SPA
        } else {
            alert(res.message);
        }
    });
};

// Инициализация Drag and Drop для виджета и панели при готовности DOM
$(document).ready(function() {
    initGlobalTimer();
    
    const container = $('#globalFloatingWidgetContainer');
    const widget = $('#globalFloatingWidget');
    const panel = $('#activeTimerPanel');
    
    if (container.length === 0) return;
    
    // Восстановление сохраненного в localStorage положения виджета
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
                
                // Проверка границ экрана
                if (finalX < 10) finalX = 10;
                if (finalY < 10) finalY = 10;
                if (finalX > ww - wWidth - 30) finalX = ww - wWidth - 30;
                if (finalY > wh - wHeight - 10) finalY = wh - wHeight - 10;
                
                container.css({top: finalY + 'px', left: finalX + 'px', right: 'auto', bottom: 'auto'});
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
    
    // Обработка клика/касания по виджету
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
    
    // Обработка клика/касания по панели
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
    
    // Перетаскивание (общий обработчик на документе)
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
            
            updateStopButtonPosition(finalX, wWidth);
        } else if (dragElement === 'panel') {
            let newY = initialTop + dy;
            if (newY > 0) newY = 0; // Не даем опуститься ниже экрана
            panel.css('transform', `translate(calc(-50% + ${dx}px), ${newY}px)`);
        }
    });
    
    // Завершение перетаскивания
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
            
            widget.removeClass('cursor-grabbing cursor-move').addClass('cursor-pointer');
            
            const distance = Math.sqrt(dx * dx + dy * dy);
            if (distance < 5) {
                // Если сдвига почти не было — это клик (пауза/старт)
                globalTogglePause();
            } else {
                // Если придвинули к нижнему краю — прикрепляем к панели
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
            
            const distance = Math.sqrt(dx * dx + dy * dy);
            // Если потянули панель достаточно сильно — отлепляем и превращаем в виджет
            if (distance > 50) {
                let finalX = clientX - container.outerWidth() / 2;
                let finalY = clientY - container.outerHeight() / 2;
                
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
                // Возвращаем панель на исходную позицию
                panel.css('transform', '');
            }
        }
        
        dragElement = null;
    });
});
