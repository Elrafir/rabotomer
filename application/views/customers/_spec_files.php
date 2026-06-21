<!-- Блок файлов одного ТЗ: прикреплённые файлы + внешние из директории + зона загрузки + ввод ссылки -->
<!-- Переменные: $spec (содержит files, external_files, files_dir, id) -->
<!-- ВАЖНО: PHP-логика сканирования файловой системы (opendir/readdir) УДАЛЕНА из View -->
<!-- Данные external_files приходят готовыми из контроллера через $spec['external_files'] -->
<div class="bg-gray-50 p-4 rounded-xl">

    <!-- Заголовок секции прикреплённых файлов -->
    <h6 class="text-xs uppercase font-bold text-gray-400 mb-2"><?= lang('cust_attached_files_title'); ?></h6>

    <!-- Список прикреплённых файлов ТЗ -->
    <div id="file-list-<?= $spec['id'] ?>" class="flex flex-wrap gap-2 mb-3">
        <?php if (empty($spec['files'])): ?>
            <!-- Заглушка при отсутствии файлов -->
            <span class="text-xs text-gray-400 italic empty-files-label"><?= lang('cust_no_files'); ?></span>
        <?php else: ?>
            <!-- Цикл по прикреплённым файлам -->
            <?php foreach ($spec['files'] as $f): ?>
                <div id="file-item-<?= $f['id'] ?>" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 text-xs shadow-sm">
                    <!-- Иконка типа файла -->
                    <span><?= get_file_icon_emoji($f['orig_name'], $f['is_link']) ?></span>
                    <?php if ($f['is_link']): ?>
                        <!-- Файл-ссылка: открывается в новой вкладке -->
                        <a href="<?= htmlspecialchars($f['filename']) ?>" target="_blank"
                           class="file-preview-trigger text-blue-600 hover:underline font-medium"
                           data-file-name="<?= htmlspecialchars($f['orig_name']) ?>"
                           data-url="<?= htmlspecialchars($f['filename']) ?>"
                           data-is-link="1"
                           data-is-external="0"><?= htmlspecialchars($f['orig_name']) ?></a>
                        <!-- Метка типа -->
                        <span class="text-gray-400 font-mono">(<?= lang('cust_file_link'); ?>)</span>
                    <?php else: ?>
                        <!-- Обычный файл: превью при наведении, клик — полный просмотр -->
                        <span class="file-preview-trigger text-gray-700 font-medium cursor-help hover:text-blue-600 transition-colors"
                              data-file-id="<?= $f['id'] ?>"
                              data-file-name="<?= htmlspecialchars($f['orig_name']) ?>"
                              data-url="<?= site_url('customers/download_file/'.$f['id']) ?>"
                              data-is-link="0"
                              data-is-external="0"><?= htmlspecialchars($f['orig_name']) ?></span>
                        <!-- Размер файла -->
                        <span class="text-gray-400 font-mono">(<?= round($f['file_size']/1024, 1) ?> KB)</span>
                        <!-- Кнопка скачивания -->
                        <a href="<?= site_url('customers/download_file/'.$f['id']) ?>"
                           class="download-icon-btn text-blue-500 hover:text-blue-700"
                           title="<?= htmlspecialchars(lang('cust_download_title'), ENT_QUOTES); ?>">📥</a>
                    <?php endif; ?>
                    <!-- Кнопка удаления файла (обрабатывается JS через делегирование) -->
                    <button class="delete-file-btn text-red-400 hover:text-red-600"
                            data-file-id="<?= $f['id'] ?>"
                            title="<?= htmlspecialchars(lang('btn_delete'), ENT_QUOTES); ?>">✖</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Драг-н-дроп зона загрузки файлов -->
    <!-- Скрытый input для выбора файлов с диска -->
    <input type="file" id="file-input-<?= $spec['id'] ?>" class="hidden" multiple data-spec-id="<?= $spec['id'] ?>">
    <!-- Зона перетаскивания файлов — все обработчики через JS-делегирование -->
    <div class="file-dropzone border-2 border-dashed border-gray-300 hover:border-blue-400 transition-colors rounded-xl p-4 text-center cursor-pointer relative"
         data-spec-id="<?= $spec['id'] ?>">
        <!-- Текст подсказки -->
        <span class="text-xs text-gray-500"><?= lang('cust_dropzone_text'); ?> <span class="text-blue-500 font-bold"><?= lang('cust_dropzone_select'); ?></span></span>
        <!-- Контейнер прогресс-бара загрузки (скрыт по умолчанию) -->
        <div id="upload-progress-container-<?= $spec['id'] ?>" class="hidden absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center rounded-xl p-4">
            <div class="w-full bg-gray-200 rounded-full h-2">
                <!-- Полоска прогресса загрузки -->
                <div id="upload-progress-<?= $spec['id'] ?>" class="bg-blue-600 h-2 rounded-full" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <!-- Внешние рабочие материалы из директории (данные готовы из контроллера) -->
    <?php if (!empty($spec['files_dir'])): ?>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <!-- Заголовок с путём к директории -->
            <h6 class="text-xs uppercase font-bold text-gray-400 mb-3">Рабочие материалы из директории: <span class="text-gray-500 font-mono select-all"><?= htmlspecialchars($spec['files_dir']) ?></span></h6>
            <?php if (empty($spec['external_files'])): ?>
                <!-- Заглушка: директория пуста или недоступна -->
                <div class="text-xs text-gray-400 italic bg-white p-4 rounded-xl border border-dashed text-center">Директория пуста или недоступна для чтения.</div>
            <?php else: ?>
                <!-- Сетка плиток внешних файлов -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    <?php foreach ($spec['external_files'] as $ef): ?>
                        <!-- Одна плитка внешнего файла -->
                        <div class="bg-white border border-gray-200 rounded-xl p-3 flex flex-col items-center text-center shadow-sm relative hover:border-blue-400 transition-colors">
                            <!-- Иконка типа файла -->
                            <span class="text-3xl mb-1.5"><?= get_file_icon_emoji($ef['name'], 0) ?></span>
                            <!-- Имя файла с превью по наведению -->
                            <span class="file-preview-trigger text-xs font-semibold text-gray-700 line-clamp-2 w-full break-all mb-1.5 cursor-help hover:text-blue-600 transition-colors"
                                  data-file-name="<?= htmlspecialchars($ef['name']) ?>"
                                  data-url="<?= site_url('customers/download_external_file?spec_id=' . $spec['id'] . '&file=' . urlencode($ef['name'])) ?>"
                                  data-spec-id="<?= $spec['id'] ?>"
                                  data-is-link="0"
                                  data-is-external="1"
                                  title="<?= htmlspecialchars($ef['name']) ?>"><?= htmlspecialchars($ef['name']) ?></span>
                            <!-- Размер и кнопка скачивания -->
                            <div class="flex items-center gap-2 mt-auto">
                                <!-- Размер в KB -->
                                <span class="text-[9px] font-mono text-gray-400"><?= round($ef['size']/1024, 1) ?> KB</span>
                                <!-- Кнопка скачивания -->
                                <a href="<?= site_url('customers/download_external_file?spec_id=' . $spec['id'] . '&file=' . urlencode($ef['name'])) ?>"
                                   class="download-icon-btn text-blue-500 hover:text-blue-700 text-xs"
                                   title="Скачать файл">📥</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Добавление внешних ссылок и загрузка по URL -->
    <div class="mt-4 pt-4 border-t border-gray-100 flex flex-col sm:flex-row gap-2">
        <!-- Поле ввода URL -->
        <input type="text" id="url-input-<?= $spec['id'] ?>"
               class="flex-grow px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
               placeholder="https://example.com/file.pdf...">
        <!-- Поле ввода заголовка ссылки -->
        <input type="text" id="url-title-<?= $spec['id'] ?>"
               class="w-full sm:w-1/4 px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
               placeholder="<?= htmlspecialchars(lang('cust_file_link'), ENT_QUOTES); ?>">
        <!-- Кнопка прикрепления ссылки (обрабатывается JS-делегированием) -->
        <button class="attach-link-btn bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold py-2 px-3 rounded-xl text-xs transition-colors flex items-center justify-center gap-1"
                data-spec-id="<?= $spec['id'] ?>">
            🔗 <?= lang('cust_link_btn'); ?>
        </button>
        <!-- Кнопка скачивания по URL (обрабатывается JS-делегированием) -->
        <button class="download-from-url-btn bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-3 rounded-xl text-xs transition-colors flex items-center justify-center gap-1"
                data-spec-id="<?= $spec['id'] ?>">
            📥 <?= lang('cust_download_btn'); ?>
        </button>
    </div>
</div>
