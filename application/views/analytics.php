<div class="w-full min-h-[80vh] pb-32 space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h1 class="text-3xl font-black text-white drop-shadow-md">Аналитика</h1>
        <button onclick="loadAnalyticsData()" class="bg-white/20 hover:bg-white/30 text-white p-2 rounded-lg backdrop-blur-md border border-white/20 shadow-sm transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            <span>Обновить</span>
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Сегодня -->
        <div class="glassmorphism-card p-6 rounded-2xl border border-white/20 shadow-lg relative overflow-hidden" style="background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0)); backdrop-filter: blur(10px);">
            <div class="absolute top-0 right-0 p-4 opacity-50 text-white">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="text-white/80 text-sm font-semibold uppercase tracking-wider mb-2">Сегодня</div>
            <div id="stat-today" class="text-4xl font-black text-white">00:00</div>
            <div class="text-white/60 text-xs mt-2">часов отработано</div>
        </div>

        <!-- Неделя -->
        <div class="glassmorphism-card p-6 rounded-2xl border border-white/20 shadow-lg relative overflow-hidden" style="background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0)); backdrop-filter: blur(10px);">
            <div class="absolute top-0 right-0 p-4 opacity-50 text-white">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
            <div class="text-white/80 text-sm font-semibold uppercase tracking-wider mb-2">На этой неделе</div>
            <div id="stat-week" class="text-4xl font-black text-white">00:00</div>
            <div class="text-white/60 text-xs mt-2">часов отработано</div>
        </div>

        <!-- Месяц -->
        <div class="glassmorphism-card p-6 rounded-2xl border border-white/20 shadow-lg relative overflow-hidden" style="background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0)); backdrop-filter: blur(10px);">
            <div class="absolute top-0 right-0 p-4 opacity-50 text-white">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            </div>
            <div class="text-white/80 text-sm font-semibold uppercase tracking-wider mb-2">В этом месяце</div>
            <div id="stat-month" class="text-4xl font-black text-white">00:00</div>
            <div class="text-white/60 text-xs mt-2">часов отработано</div>
        </div>
    </div>

    <!-- Таймлайн дня -->
    <div id="analytics-timeline" class="mb-6"></div>

    <!-- Charts Container -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Daily Bar Chart (Takes 2 columns on large screens) -->
        <div class="xl:col-span-2 glassmorphism-card p-6 rounded-2xl border border-white/20 shadow-lg" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);">
            <h2 class="text-gray-800 text-lg font-bold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                Динамика за 14 дней
            </h2>
            <div class="relative h-[300px] w-full">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>

        <!-- Projects Doughnut Chart -->
        <div class="glassmorphism-card p-6 rounded-2xl border border-white/20 shadow-lg" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);">
            <h2 class="text-gray-800 text-lg font-bold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                Распределение (30 дней)
            </h2>
            <div class="relative h-[300px] w-full flex items-center justify-center">
                <canvas id="projectsChart"></canvas>
            </div>
            <div id="projectsLegend" class="mt-4 flex flex-col gap-2 max-h-[150px] overflow-y-auto custom-scrollbar text-sm">
                <!-- Legend injected via JS -->
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/chart.umd.js?v=' . time()) ?>"></script>
<script src="<?= base_url('assets/js/analytics.js?v=' . time()) ?>"></script>
<script src="<?= base_url('assets/js/timeline.js?v=' . time()) ?>"></script>

<script>
    // Инициализация при загрузке SPA-страницы
    if (typeof loadAnalyticsData === 'function') {
        loadAnalyticsData();
    } else {
        setTimeout(() => {
            if (typeof loadAnalyticsData === 'function') loadAnalyticsData();
        }, 300);
    }
    
    // Инициализируем Таймлайн только после загрузки всех скриптов
    setTimeout(() => {
        if (typeof DailyTimeline !== 'undefined') {
            window.analyticsTimeline = new DailyTimeline('analytics-timeline', {
                title: 'Таймлайн активности',
                date: document.getElementById('analyticsDateFilter') ? document.getElementById('analyticsDateFilter').value : new Date().toISOString().split('T')[0]
            });
        }
    }, 300);
</script>
