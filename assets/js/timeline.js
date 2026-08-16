/**
 * Rabotomer v2.2.0 - Offline Daily Timeline Component
 * Renders 24-hour visual progress bar with sessions directly from IndexedDB
 * Includes breakdown statistics for PC (mains power) vs Tablet (battery/offline)
 */

function parseTimelineDate(val, fallback = null) {
    if (val === null || val === undefined || val === '') return fallback;
    if (typeof val === 'number') {
        return val < 10000000000 ? val * 1000 : val;
    }
    if (typeof val === 'string') {
        const num = Number(val);
        if (!isNaN(num) && num > 0) {
            return num < 10000000000 ? num * 1000 : num;
        }
        // Handle MySQL format 'YYYY-MM-DD HH:MM:SS' or ISO
        const normalized = val.replace(' ', 'T');
        let d = new Date(normalized).getTime();
        if (!isNaN(d)) return d;
        d = new Date(normalized + 'Z').getTime();
        if (!isNaN(d)) return d;
    }
    return fallback;
}

class OfflineDailyTimeline {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        this.date = options.date || `${y}-${m}-${d}`;
        this.title = options.title || 'Таймлайн дня';
        this.sessions = [];
        this.interval = null;

        this.renderShell();
        this.loadData();
    }

    renderShell() {
        const todayStr = this.getTodayStr();
        this.container.innerHTML = `
            <div class="timeline-widget-container p-4 bg-white rounded-2xl shadow-sm border border-gray-200/80 relative mb-6 transition-all">
                <div class="flex flex-wrap justify-between items-center gap-2 mb-2">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2 text-sm">
                        <span class="p-1.5 bg-blue-50 text-blue-600 rounded-lg">🕒</span>
                        <span>${this.title}</span> 
                        
                        <div class="flex items-center gap-1 ml-2 bg-gray-50 p-1 rounded-xl border border-gray-200/60">
                            <button class="text-gray-500 hover:text-blue-600 p-1 rounded-lg hover:bg-white transition-all" id="${this.container.id}-prev-day" title="Предыдущий день">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            </button>
                            
                            <div class="relative group cursor-pointer flex items-center justify-center px-1.5 z-10" id="${this.container.id}-date-container" title="Нажмите, чтобы выбрать дату в календаре">
                                <span class="text-xs font-bold text-gray-700 border-b border-dashed border-gray-400 pb-0.5 hover:text-blue-600 transition-colors" id="${this.container.id}-date-label">${this.formatDate(this.date)}</span>
                                <input type="date" id="${this.container.id}-date-picker" style="position: absolute; left: 0; top: 0; width: 0; height: 0; opacity: 0; pointer-events: none;" value="${this.date}">
                            </div>

                            <button class="text-gray-500 hover:text-blue-600 p-1 rounded-lg hover:bg-white transition-all" id="${this.container.id}-next-day" title="Следующий день">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </button>

                            <button class="text-[11px] font-bold px-2 py-0.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-all hidden" id="${this.container.id}-today-btn" title="Вернуться к сегодняшнему дню">
                                Сегодня
                            </button>
                        </div>
                    </h3>
                    <div class="text-xs font-mono font-black text-blue-700 bg-blue-50 px-2.5 py-1 rounded-xl border border-blue-100 shadow-sm flex items-center gap-1.5" id="${this.container.id}-clock">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>--:--</span>
                    </div>
                </div>

                <!-- Device Distribution Stats -->
                <div class="flex flex-wrap items-center gap-2 mb-3 text-xs" id="${this.container.id}-device-stats">
                    <div class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 text-slate-700 rounded-xl border border-slate-200/80 shadow-xs" title="Работа на ПК (с электричеством)">
                        <span>💻 ПК (со светом):</span>
                        <span class="font-bold font-mono text-slate-900" id="${this.container.id}-stat-desktop">0 ч 00 мин</span>
                        <span class="text-slate-400 text-[11px] font-mono" id="${this.container.id}-pct-desktop">(0%)</span>
                    </div>
                    <div class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-900 rounded-xl border border-indigo-100 shadow-xs" title="Работа на планшете (автономно без электричества)">
                        <span>📱 Планшет (автономно):</span>
                        <span class="font-bold font-mono text-indigo-950" id="${this.container.id}-stat-tablet">0 ч 00 мин</span>
                        <span class="text-indigo-400 text-[11px] font-mono" id="${this.container.id}-pct-tablet">(0%)</span>
                    </div>
                </div>

                <div class="relative w-full h-8 bg-gray-100 rounded-xl group border border-gray-200 shadow-inner" id="${this.container.id}-track">
                    <div id="${this.container.id}-sessions" class="absolute inset-0 rounded-xl overflow-hidden"></div>
                    <div id="${this.container.id}-now-marker" class="absolute z-20 hidden" style="top: -3px; bottom: -3px; width: 2px; background-color: #ef4444; box-shadow: 0 0 6px rgba(239,68,68,0.8);">
                        <div class="absolute left-1/2 transform -translate-x-1/2 w-2.5 h-2.5 rounded-full bg-red-500 shadow-xs" style="top: -2px;"></div>
                        <div class="absolute left-1/2 transform -translate-x-1/2 w-2.5 h-2.5 rounded-full bg-red-500 shadow-xs" style="bottom: -2px;"></div>
                    </div>
                </div>

                <div class="flex justify-between mt-1.5 text-[10px] text-gray-400 font-mono px-1 select-none">
                    <span>00:00</span>
                    <span>06:00</span>
                    <span>12:00</span>
                    <span>18:00</span>
                    <span>23:59</span>
                </div>

                <div id="${this.container.id}-tooltip" class="absolute hidden z-30 bg-gray-900/95 backdrop-blur text-white text-xs py-2 px-3 rounded-xl shadow-2xl pointer-events-none transform -translate-x-1/2 -translate-y-full whitespace-nowrap border border-gray-700/50" style="margin-top: -8px;">
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
        this.todayBtn = document.getElementById(`${this.container.id}-today-btn`);
        this.dateContainer = document.getElementById(`${this.container.id}-date-container`);
        this.dateInput = document.getElementById(`${this.container.id}-date-picker`);
        this.dateLabel = document.getElementById(`${this.container.id}-date-label`);

        this.updateNavigation();

        if (this.dateContainer && this.dateInput) {
            this.dateContainer.addEventListener('click', (e) => {
                e.stopPropagation();
                if (typeof this.dateInput.showPicker === 'function') {
                    try { this.dateInput.showPicker(); } catch (err) { this.dateInput.click(); }
                } else {
                    this.dateInput.click();
                }
            });
        }

        this.prevBtn.addEventListener('click', () => {
            const parts = this.date.split('-').map(Number);
            const d = new Date(parts[0], parts[1] - 1, parts[2]);
            d.setDate(d.getDate() - 1);
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            this.setDate(`${y}-${m}-${day}`);
        });

        this.nextBtn.addEventListener('click', () => {
            const parts = this.date.split('-').map(Number);
            const d = new Date(parts[0], parts[1] - 1, parts[2]);
            d.setDate(d.getDate() + 1);
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            this.setDate(`${y}-${m}-${day}`);
        });

        if (this.todayBtn) {
            this.todayBtn.addEventListener('click', () => {
                this.setDate(this.getTodayStr());
            });
        }

        this.dateInput.addEventListener('change', (e) => {
            if (e.target.value) {
                this.setDate(e.target.value);
            }
        });

        this.startClock();
    }

    getTodayStr() {
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    setDate(newDate) {
        this.date = newDate;
        if (this.dateInput) this.dateInput.value = newDate;
        if (this.dateLabel) this.dateLabel.textContent = this.formatDate(newDate);
        this.updateNavigation();
        this.loadData();
    }

    formatDate(dateStr) {
        const today = this.getTodayStr();
        if (dateStr === today) return 'Сегодня';
        
        const now = new Date();
        now.setDate(now.getDate() - 1);
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        const yesterdayStr = `${y}-${m}-${d}`;
        if (dateStr === yesterdayStr) return 'Вчера';

        const parts = dateStr.split('-');
        if (parts.length === 3) {
            return `${parts[2]}.${parts[1]}.${parts[0]}`;
        }
        return dateStr;
    }

    updateNavigation() {
        const today = this.getTodayStr();
        if (this.date >= today) {
            if (this.nextBtn) {
                this.nextBtn.disabled = true;
                this.nextBtn.classList.add('opacity-30', 'cursor-not-allowed');
            }
        } else {
            if (this.nextBtn) {
                this.nextBtn.disabled = false;
                this.nextBtn.classList.remove('opacity-30', 'cursor-not-allowed');
            }
        }

        if (this.todayBtn) {
            if (this.date === today) {
                this.todayBtn.classList.add('hidden');
            } else {
                this.todayBtn.classList.remove('hidden');
            }
        }
    }

    async loadData() {
        if (!window.db) return;
        
        const parts = this.date.split('-').map(Number);
        const dayStartDate = new Date(parts[0], parts[1] - 1, parts[2], 0, 0, 0, 0);
        const dayEndDate = new Date(parts[0], parts[1] - 1, parts[2], 23, 59, 59, 999);
        const dayStart = dayStartDate.getTime();
        const dayEnd = dayEndDate.getTime();

        const allSessions = await window.db.getAll('time_sessions');
        const tasks = await window.db.getAll('tasks');
        const taskMap = new Map();
        tasks.forEach(t => {
            if (t.id !== undefined && t.id !== null) taskMap.set(String(t.id), t);
            if (t.uuid) taskMap.set(String(t.uuid), t);
        });

        this.sessions = allSessions.map(s => {
            const sStart = parseTimelineDate(s.start_time);
            const isRunning = !s.end_time || s.is_running;
            const sEnd = parseTimelineDate(s.end_time, isRunning ? Date.now() : sStart);
            const taskId = s.task_id || s.task_uuid;
            const task = taskMap.get(String(taskId)) || {};

            return {
                id: s.id || s.uuid,
                task_id: taskId,
                task_title: task.title || (taskId ? `Задача #${taskId}` : 'Сессия без задачи'),
                color: task.color || '#3b82f6',
                start_time: sStart,
                end_time: sEnd,
                is_running: isRunning,
                device_type: (s.device_type === 'tablet' || s.device_type === 'mobile') ? 'tablet' : 'desktop',
                note: s.note || ''
            };
        }).filter(s => {
            if (!s.start_time || isNaN(s.start_time)) return false;
            // Overlaps with current selected day
            return s.start_time <= dayEnd && s.end_time >= dayStart;
        });

        this.render();
    }

    render() {
        if (!this.sessionsContainer) return;
        this.sessionsContainer.innerHTML = '';

        const parts = this.date.split('-').map(Number);
        const dayStart = new Date(parts[0], parts[1] - 1, parts[2], 0, 0, 0, 0).getTime();
        const totalMsInDay = 24 * 60 * 60 * 1000;

        let desktopMs = 0;
        let tabletMs = 0;

        this.sessions.forEach(session => {
            const startClamped = Math.max(session.start_time, dayStart);
            const dayEnd = dayStart + totalMsInDay;
            const endClamped = Math.min(session.end_time, dayEnd);

            if (endClamped <= startClamped) return;

            const durMs = endClamped - startClamped;
            const isTablet = (session.device_type === 'tablet' || session.device_type === 'mobile');
            if (isTablet) {
                tabletMs += durMs;
            } else {
                desktopMs += durMs;
            }

            const leftPct = ((startClamped - dayStart) / totalMsInDay) * 100;
            const widthPct = Math.max(((endClamped - startClamped) / totalMsInDay) * 100, 0.4);

            const block = document.createElement('div');
            block.className = 'timeline-block absolute top-0 bottom-0 transition-opacity hover:opacity-90 rounded-sm cursor-pointer ' + (isTablet ? 'border-t-2 border-indigo-400' : '');
            block.style.left = `${leftPct}%`;
            block.style.width = `${widthPct}%`;
            block.style.backgroundColor = session.color || '#3b82f6';
            if (isTablet) {
                block.style.backgroundImage = 'repeating-linear-gradient(45deg, transparent, transparent 4px, rgba(255,255,255,0.2) 4px, rgba(255,255,255,0.2) 8px)';
            }
            if (session.is_running) {
                block.classList.add('animate-pulse');
                block.style.boxShadow = `0 0 12px ${session.color || '#3b82f6'}`;
            }

            const showTooltip = (e) => {
                const sDate = new Date(session.start_time);
                const eDate = new Date(session.end_time);
                const durMin = Math.round((session.end_time - session.start_time) / 60000);
                const durStr = durMin >= 60 ? `${Math.floor(durMin / 60)}ч ${durMin % 60}м` : `${durMin} мин`;
                const fmt = (d) => `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
                const deviceBadge = isTablet 
                    ? '<div class="text-[11px] text-indigo-300 font-medium mt-0.5">📱 Планшет (автономно)</div>'
                    : '<div class="text-[11px] text-emerald-300 font-medium mt-0.5">💻 Компьютер (со светом)</div>';
                
                let content = `<strong>${session.task_title}</strong><br>${fmt(sDate)} — ${session.is_running ? 'сейчас' : fmt(eDate)} (${durStr})${deviceBadge}`;
                if (session.note) {
                    content += `<div class="opacity-80 italic text-[11px] mt-0.5">${session.note}</div>`;
                }

                const tooltipContent = document.getElementById(`${this.container.id}-tooltip-content`);
                if (tooltipContent) tooltipContent.innerHTML = content;
                this.tooltip.classList.remove('hidden');

                const rect = this.track.getBoundingClientRect();
                const mouseX = e.clientX - rect.left;
                this.tooltip.style.left = `${Math.min(Math.max(mouseX, 60), rect.width - 60)}px`;
                this.tooltip.style.top = `-8px`;
            };

            const hideTooltip = () => {
                this.tooltip.classList.add('hidden');
            };

            block.addEventListener('pointerenter', showTooltip);
            block.addEventListener('pointermove', showTooltip);
            block.addEventListener('pointerleave', hideTooltip);

            this.sessionsContainer.appendChild(block);
        });

        // Update stats
        const totalMs = desktopMs + tabletMs;
        const desktopPct = totalMs > 0 ? Math.round((desktopMs / totalMs) * 100) : 0;
        const tabletPct = totalMs > 0 ? Math.round((tabletMs / totalMs) * 100) : 0;

        const fmtMs = (ms) => {
            const min = Math.floor(ms / 60000);
            const h = Math.floor(min / 60);
            const m = min % 60;
            return `${h} ч ${String(m).padStart(2, '0')} мин`;
        };

        const statDeskEl = document.getElementById(`${this.container.id}-stat-desktop`);
        const pctDeskEl = document.getElementById(`${this.container.id}-pct-desktop`);
        const statTabEl = document.getElementById(`${this.container.id}-stat-tablet`);
        const pctTabEl = document.getElementById(`${this.container.id}-pct-tablet`);

        if (statDeskEl) statDeskEl.textContent = fmtMs(desktopMs);
        if (pctDeskEl) pctDeskEl.textContent = `(${desktopPct}%)`;
        if (statTabEl) statTabEl.textContent = fmtMs(tabletMs);
        if (pctTabEl) pctTabEl.textContent = `(${tabletPct}%)`;

        this.updateMarker();
    }

    updateMarker() {
        const now = new Date();
        const todayStr = this.getTodayStr();

        if (this.date === todayStr) {
            if (this.marker) this.marker.classList.remove('hidden');
            const parts = this.date.split('-').map(Number);
            const dayStart = new Date(parts[0], parts[1] - 1, parts[2], 0, 0, 0, 0).getTime();
            const totalMsInDay = 24 * 60 * 60 * 1000;
            const pct = ((now.getTime() - dayStart) / totalMsInDay) * 100;
            if (this.marker) this.marker.style.left = `${pct}%`;
        } else {
            if (this.marker) this.marker.classList.add('hidden');
        }
    }

    startClock() {
        const tick = () => {
            const now = new Date();
            const timeStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
            if (this.clock) {
                this.clock.innerHTML = `<span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span><span>${timeStr}</span>`;
            }
            this.updateMarker();
        };
        tick();
        this.interval = setInterval(tick, 10000);
    }

    destroy() {
        if (this.interval) clearInterval(this.interval);
    }
}

window.OfflineDailyTimeline = OfflineDailyTimeline;
