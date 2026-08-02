if (!window.loadedAnalyticsModule) {
    window.loadedAnalyticsModule = true;

    window.dailyChartInstance = null;
    window.projectsChartInstance = null;

    window.formatHours = function(seconds) {
        if (!seconds) return '0.0';
        return (seconds / 3600).toFixed(1);
    };

    window.loadAnalyticsData = function() {
        // Используем глобальный URL из body.php, если доступен
        const apiUrl = window.globalApi && window.globalApi.analytics_data ? window.globalApi.analytics_data : window.location.origin + window.location.pathname.replace(/\/analytics$/, '') + '/analytics/get_data_ajax';
        
        $.ajax({
            url: apiUrl,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    window.renderAnalytics(response);
                }
            },
            error: function(xhr) {
                console.error('Ошибка загрузки аналитики');
                if (!navigator.onLine || xhr.status === 0) {
                    alert("Нет сети. Для загрузки аналитики требуется подключение.");
                }
            }
        });
    };

    window.renderAnalytics = function(data) {
        // 1. Обновляем Summary Cards
        document.getElementById('stat-today').textContent = window.formatHours(data.summary.today);
        document.getElementById('stat-week').textContent = window.formatHours(data.summary.week);
        document.getElementById('stat-month').textContent = window.formatHours(data.summary.month);

        // 2. Bar Chart (Динамика)
        const dailyCtx = document.getElementById('dailyChart');
        if (dailyCtx) {
            if (window.dailyChartInstance) window.dailyChartInstance.destroy();
            
            const dates = Object.keys(data.daily).map(d => {
                const date = new Date(d);
                return date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' });
            });
            const hours = Object.values(data.daily).map(s => (s / 3600).toFixed(2));

            window.dailyChartInstance = new Chart(dailyCtx, {
                type: 'bar',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Часов работы',
                        data: hours,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)', // Tailwind Blue-500
                        borderColor: 'rgba(37, 99, 235, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Часы' }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        // 3. Doughnut Chart (Проекты)
        const projCtx = document.getElementById('projectsChart');
        if (projCtx) {
            if (window.projectsChartInstance) window.projectsChartInstance.destroy();

            const labels = data.projects.map(p => p.name);
            const hours = data.projects.map(p => (p.total_seconds / 3600).toFixed(2));
            
            // Генерируем цвета
            const colors = [
                '#3b82f6', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', 
                '#eab308', '#22c55e', '#14b8a6', '#06b6d4', '#64748b'
            ];
            const bgColors = data.projects.map((p, i) => (p.color && p.color !== '#9CA3AF' && p.color !== '#ffffff') ? p.color : colors[i % colors.length]);

            window.projectsChartInstance = new Chart(projCtx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: hours,
                        backgroundColor: bgColors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { display: false }
                    }
                }
            });

            // Кастомная легенда
            const legendContainer = document.getElementById('projectsLegend');
            if (legendContainer) {
                legendContainer.innerHTML = '';
                data.projects.forEach((p, i) => {
                    const color = bgColors[i];
                    const hrs = (p.total_seconds / 3600).toFixed(1);
                    
                    const item = document.createElement('div');
                    item.className = 'flex justify-between items-center py-1.5 border-b border-gray-100 last:border-0';
                    item.innerHTML = `
                        <div class="flex items-center gap-2 overflow-hidden w-2/3">
                            <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: ${color}"></span>
                            <span class="text-gray-700 truncate" title="${p.name}">${p.name}</span>
                        </div>
                        <span class="font-bold text-gray-900 ml-2 w-1/3 text-right">${hrs} ч</span>
                    `;
                    legendContainer.appendChild(item);
                });
            }
        }
    };
}
