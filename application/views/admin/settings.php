<!-- Страница системных настроек -->

<!-- Заголовок -->
<h1 class="text-4xl font-black text-gray-800 mb-8">⚙️ Настройки Системы</h1>

<!-- Карточка с формой настроек -->
<div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 max-w-2xl">
    <div class="space-y-6">

        <!-- Лимит паузы (в минутах) -->
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Лимит паузы (минуты)</label>
            <input type="number" id="pause_limit_minutes" min="1" max="480"
                value="<?= htmlspecialchars($pause_limit_minutes) ?>"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors text-sm">
            <p class="text-xs text-gray-400 mt-1">Максимальная длительность паузы для сотрудника</p>
        </div>

        <!-- Пагинация — количество записей на странице -->
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Записей на странице</label>
            <select id="per_page"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors text-sm">
                <?php foreach ([10, 25, 50, 100] as $val): ?>
                    <option value="<?= $val ?>" <?= ($per_page == $val) ? 'selected' : '' ?>><?= $val ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Директория загрузки файлов -->
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Директория загрузки</label>
            <input type="text" id="upload_dir_setting"
                value="<?= htmlspecialchars($upload_dir_setting) ?>"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors text-sm font-mono">
            <p class="text-xs text-gray-400 mt-1">Путь к директории для загружаемых файлов</p>
        </div>

        <!-- Кнопка сохранения -->
        <div class="pt-4">
            <button onclick="saveSettings()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded-xl text-xl shadow-lg transition-colors">
                💾 Сохранить настройки
            </button>
        </div>
    </div>
</div>

<script>
/**
 * Сохранение системных настроек через AJAX (jQuery)
 */
function saveSettings() {
    $.post('<?= site_url("admin/save_settings_ajax") ?>', {
        pause_limit_minutes: $('#pause_limit_minutes').val(),
        per_page:            $('#per_page').val(),
        upload_dir:          $('#upload_dir_setting').val()
    }, function(response) {
        let res = (typeof response === 'string') ? JSON.parse(response) : response;
        alert(res.message || 'Настройки сохранены');
    }).fail(function() {
        alert('Ошибка при сохранении настроек');
    });
}
</script>
