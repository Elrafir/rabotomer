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
    } else {
        // Show widget on other pages
        $('#globalFloatingWidget').removeClass('hidden');
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
    
    handle.on('mousedown touchstart', function(e) {
        isDragging = true;
        const pos = widget.position();
        initialLeft = pos.left;
        initialTop = pos.top;
        
        if (e.type === 'touchstart') {
            startX = e.originalEvent.touches[0].clientX;
            startY = e.originalEvent.touches[0].clientY;
        } else {
            startX = e.clientX;
            startY = e.clientY;
            e.preventDefault();
        }
        $('body').addClass('select-none');
    });
    
    $(document).on('mousemove touchmove', function(e) {
        if (!isDragging) return;
        
        let clientX = e.type === 'touchmove' ? e.originalEvent.touches[0].clientX : e.clientX;
        let clientY = e.type === 'touchmove' ? e.originalEvent.touches[0].clientY : e.clientY;
        
        let dx = clientX - startX;
        let dy = clientY - startY;
        
        widget.css({
            left: initialLeft + dx + 'px',
            top: initialTop + dy + 'px',
            right: 'auto',
            bottom: 'auto'
        });
    });
    
    $(document).on('mouseup touchend', function() {
        if (isDragging) {
            isDragging = false;
            $('body').removeClass('select-none');
            // Save pos
            const pos = widget.position();
            localStorage.setItem('widgetPos', JSON.stringify({top: pos.top, left: pos.left}));
        }
    });
});
