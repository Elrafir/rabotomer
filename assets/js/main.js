/** @type {number|null} Таймер для отложенного показа аккордеона истории (1 сек) */
let hoverAccordionTimer = null;

/**
 * Запускает отложенный показ истории проекта при наведении.
 * @param {HTMLElement} btn Элемент кнопки, на которую навели курсор
 * @param {string|number} taskId Идентификатор задачи для загрузки истории
 */
function startHoverAccordion(btn, taskId) {
    if (hoverAccordionTimer) clearTimeout(hoverAccordionTimer);
    hoverAccordionTimer = setTimeout(() => {
        if (typeof toggleCascadeAccordion === 'function') {
            toggleCascadeAccordion(taskId);
        }
    }, 1000);
}

/**
 * Отменяет отложенный показ истории, если мышь убрали раньше 1 секунды.
 */
function cancelHoverAccordion() {
    if (hoverAccordionTimer) {
        clearTimeout(hoverAccordionTimer);
        hoverAccordionTimer = null;
    }
}

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
        }
    } else {
        // Таймер полностью остановлен
        isPaused = false;
        hideTimerUI();
        document.title = 'Тайм-трекер';
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
    
    // По умолчанию на всех страницах (и на дашборде тоже) показываем только виджет, а панель прячем
    $('#activeTimerPanel').removeClass('translate-y-0').addClass('translate-y-full');
    
    // Анимированное появление
    $('#globalFloatingWidgetContainer').removeClass('hidden');
    setTimeout(() => {
        $('#globalFloatingWidgetContainer')
            .removeClass('opacity-0 scale-50 pointer-events-none')
            .addClass('opacity-100 scale-100');
    }, 10);
    
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
    }
}

/**
 * Скрывает все элементы UI таймера (когда таймер не активен).
 */
function hideTimerUI() {
    $('#activeTimerPanel').removeClass('translate-y-0').addClass('translate-y-full');
    
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
    const rate = $(selectElem).find(':selected').data('rate');
    const row = $(selectElem).closest('form');
    if (rate) {
        row.find('.rate-input').val(rate);
    } else {
        row.find('.rate-input').val('');
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
    $('nav a').removeClass('text-blue-200 underline');
    
    // Ищем подходящую ссылку и подсвечиваем её
    $('nav a').each(function() {
        const linkHref = $(this).attr('href');
        if (!linkHref) return;
        
        // Если ссылка - это корень (dashboard)
        if (url === window.location.origin + '/' || url === window.location.origin) {
            if (linkHref === window.location.origin + '/' || linkHref === window.location.origin + '/tasks') {
                $(this).addClass('text-blue-200 underline');
                window.isDashboardPage = true; // Обновляем глобальный флаг
            }
        } 
        // Если другая страница
        else if (url.indexOf(linkHref) !== -1 && linkHref !== window.location.origin + '/') {
            $(this).addClass('text-blue-200 underline');
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
