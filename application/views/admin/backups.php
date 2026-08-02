<!-- Страница управления резервными копиями базы данных и сайта -->

<!-- Заголовок и информация о дисковом пространстве -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-4xl font-black text-gray-800">💾 Резервные копии</h1>
        <!-- Свободное место на диске -->
        <p class="text-sm text-gray-500 mt-2">Свободно на диске: <span class="font-semibold text-gray-700"><?= htmlspecialchars($sys_space) ?></span></p>
    </div>
    <!-- Кнопки создания бэкапов -->
    <div class="flex gap-3 flex-wrap">
        <button onclick="createBackup()" id="btnCreateBackup" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl text-lg shadow-lg transition-colors">
            🗄️ Бэкап БД
        </button>
        <button onclick="createSiteBackup()" id="btnCreateSiteBackup" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-6 rounded-xl text-lg shadow-lg transition-colors">
            📦 Полный бэкап сайта
        </button>
    </div>
</div>

<!-- Карточка с таблицей бэкапов -->
<div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
    <?php if (!empty($backups)): ?>
        <!-- Таблица существующих резервных копий -->
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-4">Тип</th>
                    <th class="px-6 py-4">Имя файла</th>
                    <th class="px-6 py-4">Размер</th>
                    <th class="px-6 py-4">Дата</th>
                    <th class="px-6 py-4 text-right">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($backups as $backup): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <!-- Тип бэкапа -->
                        <td class="px-6 py-4">
                            <?php if ($backup['type'] === 'site'): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">📦 Сайт</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">🗄️ БД</span>
                            <?php endif; ?>
                        </td>
                        <!-- Имя файла -->
                        <td class="px-6 py-4 text-sm font-semibold text-gray-800 font-mono">
                            <?= htmlspecialchars($backup['filename']) ?>
                        </td>
                        <!-- Размер файла -->
                        <td class="px-6 py-4 text-sm text-gray-500 font-bold">
                            <?= htmlspecialchars($backup['size']) ?>
                        </td>
                        <!-- Дата создания -->
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= htmlspecialchars($backup['date']) ?>
                        </td>
                        <!-- Кнопки действий -->
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Скачать -->
                                <button onclick="downloadBackup('<?= htmlspecialchars($backup['filename']) ?>')"
                                    class="px-3 py-2 rounded-lg text-sm font-semibold text-blue-600 hover:bg-blue-50 transition-colors" title="Скачать">
                                    📥
                                </button>
                                <!-- Восстановить (только для SQL) -->
                                <?php if ($backup['type'] === 'db'): ?>
                                    <button onclick="restoreBackup('<?= htmlspecialchars($backup['filename']) ?>')"
                                        class="px-3 py-2 rounded-lg text-sm font-semibold text-amber-600 hover:bg-amber-50 transition-colors" title="Восстановить БД из бэкапа">
                                        ♻️
                                    </button>
                                <?php else: ?>
                                    <!-- Скачать установщик (только для полных бэкапов) -->
                                    <a href="<?= site_url('admin/download_installer') ?>"
                                        class="px-3 py-2 rounded-lg text-sm font-semibold text-purple-600 hover:bg-purple-50 transition-colors" title="Скачать файл установщика (installer.php)">
                                        🧰
                                    </a>
                                <?php endif; ?>
                                <!-- Удалить -->
                                <button onclick="deleteBackup('<?= htmlspecialchars($backup['filename']) ?>')"
                                    class="px-3 py-2 rounded-lg text-sm font-semibold text-red-600 hover:bg-red-50 transition-colors" title="Удалить">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <!-- Заглушка при отсутствии бэкапов -->
        <div class="px-6 py-16 text-center">
            <p class="text-5xl mb-4">📭</p>
            <p class="text-lg font-semibold text-gray-500">Резервных копий пока нет</p>
            <p class="text-sm text-gray-400 mt-1">Создайте бэкап БД или полный бэкап сайта</p>
        </div>
    <?php endif; ?>
</div>

<script>
/**
 * Создание бэкапа только базы данных
 */
function createBackup() {
    var btn = $('#btnCreateBackup');
    var orig = btn.html();
    btn.html('⏳ Создание...').prop('disabled', true).addClass('opacity-50');

    $.post('<?= site_url("admin/backup_db_ajax") ?>', {}, function(response) {
        var res = (typeof response === 'string') ? JSON.parse(response) : response;
        alert(res.message || 'Готово');
        if (res.status === 'success') location.reload();
        else btn.html(orig).prop('disabled', false).removeClass('opacity-50');
    }).fail(function() {
        alert('Ошибка при создании бэкапа');
        btn.html(orig).prop('disabled', false).removeClass('opacity-50');
    });
}

/**
 * Создание полного бэкапа сайта (файлы + БД в ZIP)
 */
function createSiteBackup() {
    if (!confirm('Создать полный бэкап сайта? Это может занять некоторое время.')) return;

    var btn = $('#btnCreateSiteBackup');
    var orig = btn.html();
    btn.html('⏳ Архивирование...').prop('disabled', true).addClass('opacity-50');

    $.ajax({
        url: '<?= site_url("admin/backup_site_ajax") ?>',
        type: 'POST',
        timeout: 300000, // 5 минут — архивирование может быть долгим
        success: function(response) {
            var res = (typeof response === 'string') ? JSON.parse(response) : response;
            alert(res.message || 'Готово');
            if (res.status === 'success') location.reload();
            else btn.html(orig).prop('disabled', false).removeClass('opacity-50');
        },
        error: function() {
            alert('Ошибка при создании бэкапа сайта');
            btn.html(orig).prop('disabled', false).removeClass('opacity-50');
        }
    });
}

/**
 * Скачивание файла бэкапа по имени
 */
function downloadBackup(filename) {
    window.location.href = '<?= site_url("admin/download_backup") ?>/' + encodeURIComponent(filename);
}

/**
 * Восстановление БД из бэкапа (двойное подтверждение)
 */
function restoreBackup(filename) {
    if (!confirm('⚠️ Внимание! Восстановление из бэкапа полностью заменит текущую базу данных.\nПродолжить?')) return;
    if (!confirm('🚨 ПОСЛЕДНЕЕ ПРЕДУПРЕЖДЕНИЕ:\nВсе текущие данные будут безвозвратно заменены.\n\nВы абсолютно уверены?')) return;

    $.post('<?= site_url("admin/restore_backup_ajax") ?>', { filename: filename }, function(response) {
        var res = (typeof response === 'string') ? JSON.parse(response) : response;
        alert(res.message || 'Готово');
        if (res.status === 'success') location.reload();
    }).fail(function() {
        alert('Ошибка при восстановлении');
    });
}

/**
 * Удаление файла бэкапа
 */
function deleteBackup(filename) {
    if (!confirm('Удалить резервную копию «' + filename + '»?')) return;

    $.post('<?= site_url("admin/delete_backup_ajax") ?>', { filename: filename }, function(response) {
        var res = (typeof response === 'string') ? JSON.parse(response) : response;
        if (res.status === 'success') location.reload();
        else alert(res.message || 'Ошибка удаления');
    }).fail(function() {
        alert('Ошибка при удалении');
    });
}
</script>
