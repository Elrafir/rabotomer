/**
 * Виджет Таймлайн дня
 */
if (!window.DailyTimeline) {
    window.DailyTimeline = class DailyTimeline {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;
        
        this.date = options.date || new Date().toISOString().split('T')[0];
        this.title = options.title || 'Таймлайн';
        this.apiUrl = options.apiUrl || (window.globalApi && window.globalApi.get_timeline ? window.globalApi.get_timeline : (window.location.origin + window.location.pathname.replace(/\/[a-zA-Z_]+$/, '') + '/tasks/get_timeline_ajax'));
        
        this.sessions = [];
        this.interval = null;
        
        this.renderShell();
        this.loadData();
    }
    
    renderShell() {
        this.container.innerHTML = `
            <div class="timeline-widget-container p-4 bg-white rounded-2xl shadow-sm border border-gray-100 relative mb-6">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        ${this.title} 
                        
                        <div class="flex items-center gap-1 ml-2">
                            <button class="text-gray-400 hover:text-blue-600 transition-colors p-1 rounded hover:bg-gray-100" id="${this.container.id}-prev-day" title="Предыдущий день">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            </button>
                            
                            <div class="relative group cursor-pointer flex items-center justify-center" id="${this.container.id}-date-container">
                                <span class="text-sm font-normal text-gray-500 group-hover:text-blue-600 transition-colors border-b border-dashed border-gray-300 group-hover:border-blue-300 pb-0.5" id="${this.container.id}-date-label">${this.formatDate(this.date)}</span>
                                <input type="date" id="${this.container.id}-date-picker" class="absolute w-1 h-1 opacity-0 pointer-events-none" style="bottom: 0; left: 50%;" value="${this.date}">
                            </div>

                            <button class="text-gray-400 hover:text-blue-600 transition-colors p-1 rounded hover:bg-gray-100" id="${this.container.id}-next-day" title="Следующий день">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                        </div>
                    </h3>
                    <div class="text-sm text-blue-600 font-mono bg-blue-50 px-2 py-1 rounded border border-blue-100" id="${this.container.id}-clock">--:--</div>
                </div>

                <!-- Device Distribution Stats -->
                <div class="flex flex-wrap items-center gap-2 mb-3 text-xs" id="${this.container.id}-device-stats">
                    <div class="flex items-center gap-1.5 px-2.5 py-1 bg-slate-50 text-slate-700 rounded-lg border border-slate-200/70" title="Работа на ПК (с электричеством)">
                        <span>💻 ПК (со светом):</span>
                        <span class="font-bold font-mono" id="${this.container.id}-stat-desktop">0 ч 00 мин</span>
                        <span class="text-slate-400 text-[11px]" id="${this.container.id}-pct-desktop">(0%)</span>
                    </div>
                    <div class="flex items-center gap-1.5 px-2.5 py-1 bg-indigo-50 text-indigo-800 rounded-lg border border-indigo-100" title="Работа на планшете (автономно без электричества)">
                        <span>📱 Планшет (автономно):</span>
                        <span class="font-bold font-mono" id="${this.container.id}-stat-tablet">0 ч 00 мин</span>
                        <span class="text-indigo-400 text-[11px]" id="${this.container.id}-pct-tablet">(0%)</span>
                    </div>
                </div>
                
                <div class="relative w-full h-8 bg-gray-100/80 rounded-lg group border border-gray-200/50" id="${this.container.id}-track">
                    <!-- Блоки сессий -->
                    <div id="${this.container.id}-sessions" class="absolute inset-0 rounded-lg overflow-hidden"></div>
                    
                    <!-- Маркер текущего времени -->
                    <div id="${this.container.id}-now-marker" class="absolute z-20 hidden" style="top: -4px; bottom: -4px; width: 2px; background-color: #ef4444; box-shadow: 0 0 6px rgba(239,68,68,0.6);">
                        <div class="absolute left-1/2 transform -translate-x-1/2 w-2 h-2 rounded-full bg-red-500" style="top: -2px;"></div>
                        <div class="absolute left-1/2 transform -translate-x-1/2 w-2 h-2 rounded-full bg-red-500" style="bottom: -2px;"></div>
                    </div>
                </div>
                
                <!-- Подписи часов -->
                <div class="flex justify-between mt-2 text-[10px] text-gray-400 font-medium px-1 select-none">
                    <span>00:00</span>
                    <span>06:00</span>
                    <span>12:00</span>
                    <span>18:00</span>
                    <span>23:59</span>
                </div>
                
                <!-- Всплывающая подсказка -->
                <div id="${this.container.id}-tooltip" class="absolute hidden z-30 bg-gray-900 text-white text-xs py-2 px-3 rounded-lg shadow-xl pointer-events-none transform -translate-x-1/2 -translate-y-full whitespace-nowrap" style="margin-top: -8px;">
                    <div id="${this.container.id}-tooltip-content"></div>
                    <div class="absolute left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45" style="bottom: -4px;"></div>
                </div>
            </div>
        `;
        
        this.track = document.getElementById(`${this.container.id}-track`);
        this.sessionsContainer = document.getElementById(`${this.container.id}-sessions`);
        this.marker = document.getElementById(`${this.container.id}-now-marker`);
        this.clock = document.getElementById(`${this.container.id}-clock`);
        this.tooltip = document.getElementById(`${this.container.id}-tooltip`);
        
        this.prevBtn = document.getElementById(`${this.container.id}-prev-day`);
        this.nextBtn = document.getElementById(`${this.container.id}-next-day`);
        this.dateInput = document.getElementById(`${this.container.id}-date-picker`);
        this.dateLabel = document.getElementById(`${this.container.id}-date-label`);
        this.dateContainer = document.getElementById(`${this.container.id}-date-container`);
        
        // Обновляем состояние кнопок при рендере оболочки
        this.updateNavigation();
        
        // Слушатели для навигации
        this.prevBtn.addEventListener('click', () => {
            const d = new Date(this.date);
            d.setDate(d.getDate() - 1);
            this.setDate(d.toISOString().split('T')[0]);
        });
        
        this.nextBtn.addEventListener('click', () => {
            const d = new Date(this.date);
            d.setDate(d.getDate() + 1);
            // Не даем уйти в будущее
            const today = new Date().toISOString().split('T')[0];
            if (d.toISOString().split('T')[0] <= today) {
                this.setDate(d.toISOString().split('T')[0]);
            }
        });
        
        this.dateInput.addEventListener('change', (e) => {
            if (e.target.value) {
                const today = new Date().toISOString().split('T')[0];
                if (e.target.value > today) {
                    this.setDate(today);
                } else {
                    this.setDate(e.target.value);
                }
            }
        });
        
        // Открытие календарика при клике на контейнер с датой
        if (this.dateContainer) {
            this.dateContainer.addEventListener('click', (e) => {
                if (e.target !== this.dateInput) {
                    try {
                        if (typeof this.dateInput.showPicker === 'function') {
                            this.dateInput.showPicker();
                        } else {
                            this.dateInput.focus();
                        }
                    } catch (err) {
                        this.dateInput.focus();
                    }
                }
            });
        }
        
        // Слушатели для тултипа
        this.sessionsContainer.addEventListener('mousemove', (e) => {
            const block = e.target.closest('.timeline-block');
            if (block) {
                const title = block.dataset.title;
                const timeStr = block.dataset.time;
                const device = block.dataset.device || 'desktop';
                const isTablet = (device === 'tablet' || device === 'mobile');
                const deviceBadge = isTablet 
                    ? '<span class="inline-flex items-center gap-1 text-indigo-300 font-medium text-[11px] mt-0.5">📱 Планшет (автономно)</span>'
                    : '<span class="inline-flex items-center gap-1 text-emerald-300 font-medium text-[11px] mt-0.5">💻 Компьютер (со светом)</span>';

                this.tooltip.querySelector(`#${this.container.id}-tooltip-content`).innerHTML = `
                    <strong>${title}</strong><br>
                    <span class="text-gray-300">${timeStr}</span><br>
                    ${deviceBadge}
                `;
                
                const trackRect = this.track.getBoundingClientRect();
                const containerRect = this.container.getBoundingClientRect();
                
                let leftPos = e.clientX - containerRect.left;
                
                this.tooltip.style.left = `${leftPos}px`;
                this.tooltip.style.top = `${this.track.offsetTop - 5}px`;
                this.tooltip.classList.remove('hidden');
            } else {
                this.tooltip.classList.add('hidden');
            }
        });
        
        this.sessionsContainer.addEventListener('mouseleave', () => {
            this.tooltip.classList.add('hidden');
        });
    }
    
    formatDate(dateStr) {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    formatDuration(totalSec) {
        const h = Math.floor(totalSec / 3600);
        const m = Math.floor((totalSec % 3600) / 60);
        return `${h} ч ${String(m).padStart(2, '0')} мин`;
    }
    
    setDate(newDate) {
        this.date = newDate;
        this.dateLabel.textContent = this.formatDate(this.date);
        this.dateInput.value = this.date;
        this.updateNavigation();
        this.loadData();
    }
    
    updateNavigation() {
        const today = new Date().toISOString().split('T')[0];
        if (this.date >= today) {
            this.nextBtn.classList.add('opacity-30', 'pointer-events-none');
        } else {
            this.nextBtn.classList.remove('opacity-30', 'pointer-events-none');
        }
    }
    
    loadData() {
        $.ajax({
            url: this.apiUrl,
            type: 'GET',
            data: { date: this.date },
            dataType: 'json',
            success: (res) => {
                if (res.status === 'success') {
                    this.sessions = res.sessions;
                    this.renderSessions();
                    this.startLiveUpdate();
                }
            }
        });
    }
    
    parseTimeToPercentage(timeStr) {
        // timeStr format: "HH:MM:SS"
        const parts = timeStr.split(':');
        if (parts.length < 2) return 0;
        const h = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10);
        const s = parts.length > 2 ? parseInt(parts[2], 10) : 0;
        return ((h + m / 60 + s / 3600) / 24) * 100;
    }

    parseTimeToSeconds(timeStr) {
        const parts = timeStr.split(':');
        if (parts.length < 2) return 0;
        const h = parseInt(parts[0], 10) || 0;
        const m = parseInt(parts[1], 10) || 0;
        const s = parseInt(parts[2], 10) || 0;
        return (h * 3600) + (m * 60) + s;
    }
    
    renderSessions() {
        this.sessionsContainer.innerHTML = '';
        const now = new Date();
        const todayStr = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-' + String(now.getDate()).padStart(2,'0');
        const isToday = (this.date === todayStr);
        
        let nowPct = 0;
        let nowSec = 0;
        if (isToday) {
            nowPct = this.parseTimeToPercentage(`${now.getHours()}:${now.getMinutes()}:${now.getSeconds()}`);
            nowSec = (now.getHours() * 3600) + (now.getMinutes() * 60) + now.getSeconds();
        }

        let desktopSec = 0;
        let tabletSec = 0;
        
        this.sessions.forEach(session => {
            const startPct = this.parseTimeToPercentage(session.start_time_only);
            const startSeconds = this.parseTimeToSeconds(session.start_time_only);
            let endPct;
            let endSeconds;
            let timeText = session.start_time_only.substring(0,5) + ' - ';
            
            if (session.is_active) {
                // Если сессия активна, тянем до текущего времени
                endPct = isToday ? nowPct : 100;
                endSeconds = isToday ? nowSec : 86400;
                timeText += 'В процессе';
            } else {
                endPct = this.parseTimeToPercentage(session.end_time_only);
                endSeconds = this.parseTimeToSeconds(session.end_time_only);
                timeText += session.end_time_only.substring(0,5);
            }
            
            // Если конец меньше начала (переход через полночь), обрезаем на 100%
            if (endPct < startPct) {
                endPct = 100;
                endSeconds = 86400;
            }
            
            const durSec = Math.max(0, endSeconds - startSeconds);
            const deviceType = session.device_type || 'desktop';
            if (deviceType === 'tablet' || deviceType === 'mobile') {
                tabletSec += durSec;
            } else {
                desktopSec += durSec;
            }

            const widthPct = Math.max(0.2, endPct - startPct); // Минимум 0.2% ширины чтобы было видно
            const isTablet = (deviceType === 'tablet' || deviceType === 'mobile');
            
            const block = document.createElement('div');
            block.className = `timeline-block absolute h-full top-0 cursor-pointer hover:brightness-110 transition-all ${session.is_active ? 'animate-pulse opacity-90 border-r border-white/50' : ''} ${isTablet ? 'border-t-2 border-indigo-400' : ''}`;
            block.style.left = `${startPct}%`;
            block.style.width = `${widthPct}%`;
            block.style.backgroundColor = session.color || '#3b82f6';
            if (isTablet) {
                // Subtle stripe overlay for tablet/mobile sessions
                block.style.backgroundImage = 'repeating-linear-gradient(45deg, transparent, transparent 4px, rgba(255,255,255,0.2) 4px, rgba(255,255,255,0.2) 8px)';
            }
            if (!session.color) block.style.opacity = '0.5'; // Если пауза, делаем прозрачным
            
            // Сохраняем данные для тултипа
            block.dataset.title = session.title;
            block.dataset.time = timeText;
            block.dataset.device = deviceType;
            
            this.sessionsContainer.appendChild(block);
        });

        // Обновляем статистику устройств
        const totalWorkSec = desktopSec + tabletSec;
        const desktopPct = totalWorkSec > 0 ? Math.round((desktopSec / totalWorkSec) * 100) : 0;
        const tabletPct = totalWorkSec > 0 ? Math.round((tabletSec / totalWorkSec) * 100) : 0;

        const statDeskEl = document.getElementById(`${this.container.id}-stat-desktop`);
        const pctDeskEl = document.getElementById(`${this.container.id}-pct-desktop`);
        const statTabEl = document.getElementById(`${this.container.id}-stat-tablet`);
        const pctTabEl = document.getElementById(`${this.container.id}-pct-tablet`);

        if (statDeskEl) statDeskEl.textContent = this.formatDuration(desktopSec);
        if (pctDeskEl) pctDeskEl.textContent = `(${desktopPct}%)`;
        if (statTabEl) statTabEl.textContent = this.formatDuration(tabletSec);
        if (pctTabEl) pctTabEl.textContent = `(${tabletPct}%)`;
        
        this.updateClockAndMarker();
    }
    
    updateClockAndMarker() {
        const now = new Date();
        const todayStr = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-' + String(now.getDate()).padStart(2,'0');
        const isToday = (this.date === todayStr);
        
        // Часы
        this.clock.textContent = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
        
        // Маркер времени (только для сегодняшнего дня)
        if (isToday) {
            const pct = this.parseTimeToPercentage(`${now.getHours()}:${now.getMinutes()}:${now.getSeconds()}`);
            this.marker.style.left = `${pct}%`;
            this.marker.classList.remove('hidden');
        } else {
            this.marker.classList.add('hidden');
        }
    }
    
    startLiveUpdate() {
        if (this.interval) clearInterval(this.interval);
        
        // Проверяем, есть ли активная сессия для обновления её длины
        const hasActive = this.sessions.some(s => s.is_active);
        const now = new Date();
        const todayStr = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-' + String(now.getDate()).padStart(2,'0');
        const isToday = (this.date === todayStr);
        
        if (isToday || hasActive) {
            this.interval = setInterval(() => {
                this.updateClockAndMarker();
                if (hasActive && isToday) {
                    // Перерисовываем полностью, чтобы активный блок рос
                    this.renderSessions(); 
                }
            }, 60000); // Раз в минуту
        }
    }
}
}
