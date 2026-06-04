let hoverAccordionTimer = null;

function startHoverAccordion(btn, taskId) {
    if (hoverAccordionTimer) clearTimeout(hoverAccordionTimer);
    hoverAccordionTimer = setTimeout(() => {
        if (typeof toggleCascadeAccordion === 'function') {
            toggleCascadeAccordion(taskId);
        }
    }, 1000);
}

function cancelHoverAccordion() {
    if (hoverAccordionTimer) {
        clearTimeout(hoverAccordionTimer);
        hoverAccordionTimer = null;
    }
}

// --- GLOBAL TIMER LOGIC ---
let globalTimerInterval = null;
let globalTitleInterval = null;
let localStartTime = null;
let initialSessionSeconds = 0;
let totalAccumulatedSeconds = 0;
let currentTaskTitle = "";
let currentTaskId = null;
let isPaused = false;
let blinkState = false;

function formatTime(totalSeconds) {
    const h = Math.floor(totalSeconds / 3600).toString().padStart(2, '0');
    const m = Math.floor((totalSeconds % 3600) / 60).toString().padStart(2, '0');
    const s = (totalSeconds % 60).toString().padStart(2, '0');
    return `${h}:${m}:${s}`;
}

function updateGlobalTimerDisplay() {
    if (localStartTime === null) return;
    
    const elapsedSinceLoad = Math.floor((Date.now() - localStartTime) / 1000);
    const currentSessionSeconds = initialSessionSeconds + elapsedSinceLoad;
    
    if (currentSessionSeconds >= 0) {
        const timeStr = formatTime(currentSessionSeconds);
        const totalStr = formatTime(totalAccumulatedSeconds + currentSessionSeconds);
        
        // Update DOM if on dashboard
        if (window.isDashboardPage) {
            $('#timerDisplay').text(timeStr);
            $('#totalTimerDisplay').text(totalStr);
        }
        
        // Update Global Widget
        $('#globalWidgetTimer').text(timeStr);
        
        // Update Title
        if (!isPaused) {
            document.title = `🟢 ${timeStr} - ${currentTaskTitle}`;
        }
    }
}

function blinkPausedTitle() {
    if (!isPaused) return;
    const timeStr = formatTime(initialSessionSeconds); // time is frozen
    blinkState = !blinkState;
    document.title = blinkState ? `🔴 ПАУЗА - ${currentTaskTitle}` : `🔴 ${timeStr} - ${currentTaskTitle}`;
}

function initGlobalTimer() {
    if (globalTimerInterval) clearInterval(globalTimerInterval);
    if (globalTitleInterval) clearInterval(globalTitleInterval);
    
    // Check localStorage for paused state first
    const pausedDataStr = localStorage.getItem('pausedTimerInfo');
    let pausedData = null;
    if (pausedDataStr) {
        try { pausedData = JSON.parse(pausedDataStr); } catch (e) {}
    }

    if (window.globalActiveSession) {
        // Active session exists!
        isPaused = false;
        localStorage.removeItem('pausedTimerInfo'); // Clear pause if actually running
        
        currentTaskTitle = window.globalActiveSession.task_title;
        currentTaskId = window.globalActiveSession.task_id;
        initialSessionSeconds = parseInt(window.globalActiveSession.current_elapsed) || 0;
        totalAccumulatedSeconds = parseInt(window.globalActiveSession.total_accumulated) || 0;
        localStartTime = Date.now();
        
        showTimerUI();
        updateGlobalTimerDisplay();
        globalTimerInterval = setInterval(updateGlobalTimerDisplay, 1000);
        
    } else if (pausedData) {
        // Paused session exists!
        isPaused = true;
        currentTaskTitle = pausedData.task_title;
        currentTaskId = pausedData.task_id;
        initialSessionSeconds = parseInt(pausedData.current_elapsed) || 0;
        totalAccumulatedSeconds = parseInt(pausedData.total_accumulated) || 0;
        localStartTime = Date.now(); // Not used for counting, but required for format
        
        showTimerUI();
        
        // Setup Paused UI
        if (window.isDashboardPage) {
            $('#timerDisplay').text(formatTime(initialSessionSeconds));
            $('#totalTimerDisplay').text(formatTime(totalAccumulatedSeconds + initialSessionSeconds));
            $('#btnPauseDashboard').html(`▶ ${window.globalLang.btn_continue}`).removeClass('bg-yellow-500 hover:bg-yellow-400').addClass('bg-green-500 hover:bg-green-400');
        }
        $('#globalWidgetTimer').text(formatTime(initialSessionSeconds));
        $('#globalWidgetPauseBtn').html('▶').removeClass('bg-yellow-500 hover:bg-yellow-400').addClass('bg-green-500 hover:bg-green-400');
        
        globalTitleInterval = setInterval(blinkPausedTitle, 1000);
        blinkPausedTitle();
    } else {
        hideTimerUI();
        document.title = 'Тайм-трекер';
    }
}

function showTimerUI() {
    $('#globalWidgetTitle').text(currentTaskTitle);
    
    if (window.isDashboardPage) {
        // Show dashboard panel
        $('#activeTimerTitle').text(currentTaskTitle);
        $('#activeTimerPanel').removeClass('translate-y-full').addClass('translate-y-0');
        $('#globalFloatingWidget').addClass('hidden'); // Hide widget on dashboard
        
        if (isPaused) {
            $('#timerDisplay').text(formatTime(initialSessionSeconds));
            $('#totalTimerDisplay').text(formatTime(totalAccumulatedSeconds + initialSessionSeconds));
            $('#btnPauseDashboard').html(`▶ ${window.globalLang.btn_continue}`).removeClass('bg-yellow-500 hover:bg-yellow-400').addClass('bg-green-500 hover:bg-green-400');
        }
    } else {
        // Show widget on other pages
        $('#globalFloatingWidget').removeClass('hidden');
        if (isPaused) {
            $('#globalWidgetTimer').text(formatTime(initialSessionSeconds));
            $('#globalWidgetPauseBtn').html('▶').removeClass('bg-yellow-500 hover:bg-yellow-400').addClass('bg-green-500 hover:bg-green-400');
        }
    }
}

function hideTimerUI() {
    if (window.isDashboardPage) {
        $('#activeTimerPanel').removeClass('translate-y-0').addClass('translate-y-full');
    }
    $('#globalFloatingWidget').addClass('hidden');
}

// Actions
function actionTogglePause() {
    if (!currentTaskId) return;
    
    if (isPaused) {
        // Resume
        $.post(window.globalApi.start, { task_id: currentTaskId }, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                localStorage.removeItem('pausedTimerInfo');
                window.location.reload(); // Reload to get new globalActiveSession
            } else {
                alert(res.message);
            }
        });
    } else {
        // Pause
        $.post(window.globalApi.stop, { note: 'Пауза' }, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success' || res.status === 'spam') {
                // Save paused state
                const elapsedSinceLoad = Math.floor((Date.now() - localStartTime) / 1000);
                const currentSessionSeconds = initialSessionSeconds + elapsedSinceLoad;
                
                const pausedInfo = {
                    task_id: currentTaskId,
                    task_title: currentTaskTitle,
                    current_elapsed: currentSessionSeconds,
                    total_accumulated: totalAccumulatedSeconds
                };
                localStorage.setItem('pausedTimerInfo', JSON.stringify(pausedInfo));
                window.location.reload(); // Reload to apply pause UI
            } else {
                alert(res.message);
            }
        });
    }
}

function actionStopTimer() {
    const note = prompt(window.globalLang.js_prompt_stop_timer, "");
    if (note === null) return;

    $.post(window.globalApi.stop, { note: note }, function(response) {
        let res = JSON.parse(response);
        if (res.status === 'success' || res.status === 'spam') {
            if (res.status === 'spam') alert(res.message);
            localStorage.removeItem('pausedTimerInfo');
            window.location.reload();
        } else {
            alert(res.message);
        }
    });
}

function globalTogglePause() {
    actionTogglePause();
}

// Dashboard functions (used by onclick in dashboard.php)
window.pauseTimer = function() {
    actionTogglePause();
};
window.stopTimer = function() {
    actionStopTimer();
};
window.startTimer = function(taskId) {
    $.post(window.globalApi.start, { task_id: taskId }, function(response) {
        let res = JSON.parse(response);
        if (res.status === 'success') {
            localStorage.removeItem('pausedTimerInfo');
            window.location.reload();
        } else {
            alert(res.message);
        }
    });
};

// --- DRAG AND DROP WIDGET ---
$(document).ready(function() {
    initGlobalTimer();
    
    const widget = $('#globalFloatingWidget');
    const handle = widget.find('.drag-handle');
    
    if (widget.length === 0) return;
    
    // Load pos
    const savedPos = localStorage.getItem('widgetPos');
    if (savedPos) {
        try {
            const pos = JSON.parse(savedPos);
            widget.css({top: pos.top + 'px', left: pos.left + 'px', right: 'auto', bottom: 'auto'});
        } catch(e){}
    }
    
    let isDragging = false;
    let startX, startY, initialLeft, initialTop;
    
    // Используем Pointer Events для идеальной поддержки стилуса и тачскрина
    handle.css('touch-action', 'none'); // Блокируем скролл при перетаскивании
    
    handle.on('pointerdown', function(e) {
        isDragging = true;
        const pos = widget.position();
        initialLeft = pos.left;
        initialTop = pos.top;
        
        startX = e.clientX;
        startY = e.clientY;
        
        handle[0].setPointerCapture(e.pointerId);
        $('body').addClass('select-none');
    });
    
    handle.on('pointermove', function(e) {
        if (!isDragging) return;
        
        let dx = e.clientX - startX;
        let dy = e.clientY - startY;
        
        widget.css({
            left: initialLeft + dx + 'px',
            top: initialTop + dy + 'px',
            right: 'auto',
            bottom: 'auto'
        });
    });
    
    handle.on('pointerup pointercancel', function(e) {
        if (isDragging) {
            isDragging = false;
            handle[0].releasePointerCapture(e.pointerId);
            $('body').removeClass('select-none');
            // Save pos
            const pos = widget.position();
            localStorage.setItem('widgetPos', JSON.stringify({top: pos.top, left: pos.left}));
        }
    });
});

// --- GLOBAL ADD MODAL ---
function openGlobalAddModal() {
    $('#globalAddModal').removeClass('hidden');
    // Focus the input
    setTimeout(() => $('#globalAddModal input[name="title"]').focus(), 100);
}

function closeGlobalAddModal() {
    $('#globalAddModal').addClass('hidden');
}

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

    $(window).on('popstate', function(e) {
        loadAjaxPage(window.location.href, false);
    });
});

function loadAjaxPage(url, push = true) {
    // Показываем какой-нибудь индикатор загрузки (например, затемняем контент)
    $('#main-content').css('opacity', '0.5');
    
    $.ajax({
        url: url,
        type: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }, // Явно указываем AJAX
        success: function(html) {
            // Обновляем контент
            $('#main-content').html(html);
            $('#main-content').css('opacity', '1');
            
            // Обновляем URL
            if (push) {
                window.history.pushState(null, '', url);
            }
            
            // Обновляем визуальное состояние меню
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
