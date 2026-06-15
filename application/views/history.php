<!-- 
  Страница журнала сессий (История).
  Отображается в центральной колонке трехколоночного шаблона.
  Левая колонка содержит меню 'sidebars/statistics'.
  
  Для администраторов доступен полноценный CRUD (создание, редактирование, удаление)
  над собственными сессиями времени.
-->
<div class="w-full">
    <div class="flex justify-between items-end mb-4">
        <div class="flex items-center gap-6">
            <img src="<?= base_url('assets/img/history_logo.png') ?>" alt="History Logo" class="w-16 h-16 object-cover rounded-2xl shadow-sm">
            <h2 class="text-3xl font-black text-gray-800"><?= lang('history_title'); ?></h2>
        </div>
        
        <!-- Кнопка создания новой сессии вручную. Видна только администраторам. -->
        <?php if (!empty($is_admin)): ?>
            <button onclick="openAddSessionModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <?= lang('lbl_session_add'); ?>
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($sessions)): ?>
        <div class="bg-white p-12 rounded-3xl border border-gray-100 text-center shadow-sm">
            <span class="text-6xl mb-4 block text-gray-300">📖</span>
            <p class="text-2xl text-gray-400 font-medium"><?= lang('history_empty'); ?></p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead class="bg-gray-50 border-b border-gray-100 text-gray-500 uppercase tracking-wider text-sm font-bold">
                        <tr>
                            <th class="px-6 py-4 w-1/5"><?= lang('history_col_date'); ?></th>
                            <th class="px-6 py-4 w-1/4"><?= lang('history_col_task'); ?></th>
                            <th class="px-6 py-4 w-1/6"><?= lang('history_col_duration'); ?></th>
                            <th class="px-6 py-4 w-auto"><?= lang('history_col_note'); ?></th>
                            <!-- Дополнительная колонка действий для администраторов -->
                            <?php if (!empty($is_admin)): ?>
                                <th class="px-6 py-4 w-[120px] text-right"><?= lang('lbl_actions'); ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody" class="divide-y divide-gray-100">
                        <?php foreach ($sessions as $s): ?>
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="px-6 py-5">
                                    <div class="flex flex-col">
                                        <span class="text-gray-900 font-bold text-lg"><?= $s['start_formatted']; ?></span>
                                        <span class="text-gray-400 text-sm flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                            <?= $s['end_formatted']; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <?php $color_bg = !empty($s['color']) ? "background-color: {$s['color']};" : "background-color: #e5e7eb;"; ?>
                                        <div class="w-4 h-4 rounded-full flex-shrink-0 shadow-sm" style="<?= $color_bg ?>"></div>
                                        <span class="text-gray-800 font-semibold text-lg"><?= htmlspecialchars($s['task_title'] ?? ''); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold font-mono bg-blue-50 text-blue-600 border border-blue-100">
                                        <?= $s['duration_formatted']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <?php if (!empty($s['note_safe'])): ?>
                                        <div class="text-gray-600 italic bg-gray-50 border-l-4 border-gray-200 px-4 py-2 rounded-r-lg group-hover:bg-white transition-colors">
                                            <?= $s['note_safe']; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-300">—</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Кнопки действий (редактирование, удаление) для администраторов -->
                                <?php if (!empty($is_admin)): ?>
                                    <td class="px-6 py-5 text-right whitespace-nowrap">
                                        <div class="flex justify-end gap-2">
                                            <button onclick="openEditSessionModal(<?= $s['id'] ?>, <?= $s['task_id'] ?>, '<?= date('Y-m-d\TH:i', strtotime($s['start_time'])) ?>', '<?= date('Y-m-d\TH:i', strtotime($s['end_time'])) ?>', '<?= addslashes($s['note_safe']) ?>')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 p-2 rounded-lg transition-colors" title="<?= htmlspecialchars(lang('btn_edit'), ENT_QUOTES); ?>">
                                                ✏️
                                            </button>
                                            <button onclick="deleteSession(<?= $s['id'] ?>)" class="bg-red-50 hover:bg-red-100 text-red-600 p-2 rounded-lg transition-colors" title="<?= htmlspecialchars(lang('btn_delete'), ENT_QUOTES); ?>">
                                                🗑️
                                            </button>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Кнопочная пагинация удалена в пользу бесконечного AJAX-скролла -->

    <?php endif; ?>
</div>

<?php if (!empty($is_admin)): ?>
    <!-- Модальное окно добавления сессии -->
    <div id="addSessionModal" onclick="closeAddSessionModal()" class="hidden fixed inset-0 z-[120] bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 transform transition-all relative">
            <button onclick="closeAddSessionModal()" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <h3 class="text-2xl font-bold mb-6 text-gray-800"><?= lang('lbl_session_add'); ?></h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('lbl_task'); ?></label>
                    <select id="add_task_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value=""><?= lang('lbl_select_task'); ?></option>
                        <?php if (!empty($tasks)): ?>
                            <?php foreach ($tasks as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?> <?= !empty($t['customer_name']) ? '['.htmlspecialchars($t['customer_name']).']' : '' ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('lbl_start_time'); ?></label>
                    <input type="datetime-local" id="add_start_time" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('lbl_end_time'); ?></label>
                    <input type="datetime-local" id="add_end_time" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('lbl_note_result'); ?></label>
                    <textarea id="add_note" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="<?= htmlspecialchars(lang('lbl_what_was_done_placeholder'), ENT_QUOTES); ?>"></textarea>
                </div>
                
                <button onclick="submitAddSession()" class="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-colors">
                    <?= lang('btn_save'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования сессии -->
    <div id="editSessionModal" onclick="closeEditSessionModal()" class="hidden fixed inset-0 z-[120] bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 transform transition-all relative">
            <button onclick="closeEditSessionModal()" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <h3 class="text-2xl font-bold mb-6 text-gray-800"><?= lang('lbl_session_edit'); ?></h3>
            
            <input type="hidden" id="edit_session_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('lbl_task'); ?></label>
                    <select id="edit_task_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value=""><?= lang('lbl_select_task'); ?></option>
                        <?php if (!empty($tasks)): ?>
                            <?php foreach ($tasks as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?> <?= !empty($t['customer_name']) ? '['.htmlspecialchars($t['customer_name']).']' : '' ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('lbl_start_time'); ?></label>
                    <input type="datetime-local" id="edit_start_time" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('lbl_end_time'); ?></label>
                    <input type="datetime-local" id="edit_end_time" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('lbl_note_result'); ?></label>
                    <textarea id="edit_note" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="<?= htmlspecialchars(lang('lbl_what_was_done_placeholder'), ENT_QUOTES); ?>"></textarea>
                </div>
                
                <button onclick="submitEditSession()" class="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-colors">
                    <?= lang('btn_save'); ?> изменения
                </button>
            </div>
        </div>
    </div>

    <!-- Клиентские скрипты для AJAX CRUD операций над сессиями -->
    <script>
        // Открытие модального окна добавления сессии
        function openAddSessionModal() {
            $('#add_task_id').val('');
            $('#add_start_time').val('');
            $('#add_end_time').val('');
            $('#add_note').val('');
            $('#addSessionModal').removeClass('hidden');
        }

        // Закрытие модального окна добавления сессии
        function closeAddSessionModal() {
            $('#addSessionModal').addClass('hidden');
        }

        // Открытие модального окна редактирования сессии с предзаполнением
        function openEditSessionModal(sessionId, taskId, startTime, endTime, note) {
            $('#edit_session_id').val(sessionId);
            $('#edit_task_id').val(taskId);
            $('#edit_start_time').val(startTime);
            $('#edit_end_time').val(endTime);
            $('#edit_note').val(note);
            $('#editSessionModal').removeClass('hidden');
        }

        // Закрытие модального окна редактирования сессии
        function closeEditSessionModal() {
            $('#editSessionModal').addClass('hidden');
        }

        // AJAX-отправка новой сессии на сохранение
        function submitAddSession() {
            var taskId = $('#add_task_id').val();
            var startTime = $('#add_start_time').val();
            var endTime = $('#add_end_time').val();
            var note = $('#add_note').val();

            if (!taskId || !startTime || !endTime) {
                alert('<?= htmlspecialchars(lang('js_err_required_fields'), ENT_QUOTES); ?>');
                return;
            }

            $.post('<?= site_url("history/add_session_ajax") ?>', {
                task_id: taskId,
                start_time: startTime,
                end_time: endTime,
                note: note
            }, function(response) {
                try {
                    var res = JSON.parse(response);
                    if (res.status === 'success') {
                        closeAddSessionModal();
                        window.location.reload(); // Перезагружаем для обновления списка сессий
                    } else {
                        alert(res.message);
                    }
                } catch(e) {
                    console.error(e);
                    alert('<?= htmlspecialchars(lang('js_err_system_save'), ENT_QUOTES); ?>');
                }
            });
        }

        // AJAX-отправка изменений сессии на обновление
        function submitEditSession() {
            var sessionId = $('#edit_session_id').val();
            var taskId = $('#edit_task_id').val();
            var startTime = $('#edit_start_time').val();
            var endTime = $('#edit_end_time').val();
            var note = $('#edit_note').val();

            if (!sessionId || !taskId || !startTime || !endTime) {
                alert('<?= htmlspecialchars(lang('js_err_required_fields'), ENT_QUOTES); ?>');
                return;
            }

            $.post('<?= site_url("history/edit_session_ajax") ?>', {
                session_id: sessionId,
                task_id: taskId,
                start_time: startTime,
                end_time: endTime,
                note: note
            }, function(response) {
                try {
                    var res = JSON.parse(response);
                    if (res.status === 'success') {
                        closeEditSessionModal();
                        window.location.reload(); // Перезагружаем для отображения измененных данных
                    } else {
                        alert(res.message);
                    }
                } catch(e) {
                    console.error(e);
                    alert('<?= htmlspecialchars(lang('js_err_system_save_changes'), ENT_QUOTES); ?>');
                }
            });
        }

        // AJAX-запрос на удаление выбранной сессии с подтверждением
        function deleteSession(sessionId) {
            if (!confirm('<?= htmlspecialchars(lang('js_confirm_delete'), ENT_QUOTES); ?>')) {
                return;
            }

            $.post('<?= site_url("history/delete_session_ajax") ?>', {
                session_id: sessionId
            }, function(response) {
                try {
                    var res = JSON.parse(response);
                    if (res.status === 'success') {
                        window.location.reload(); // Перезагружаем для обновления списка
                    } else {
                        alert(res.message);
                    }
                } catch(e) {
                    console.error(e);
                    alert('<?= htmlspecialchars(lang('js_err_delete_session_fail'), ENT_QUOTES); ?>');
                }
            });
        }
    </script>
<?php endif; ?>

<!-- Скрипт бесконечного AJAX-скролла для журнала активности (История) -->
<script>
    // Инициализируем начальное смещение для подгрузки записей (на основе размера страницы из PHP)
    let historyOffset = <?= isset($per_page) ? $per_page : 25; ?>;
    // Указываем размер страницы по умолчанию (лимит загружаемых строк за один раз)
    let historyLimit = <?= isset($per_page) ? $per_page : 25; ?>;
    // Флаг, определяющий, есть ли еще доступные записи для подгрузки из базы
    let historyHasMore = true;
    // Флаг защиты от повторных параллельных AJAX-запросов во время прокрутки
    let historyIsLoading = false;

    // Вешаем обработчик события скролла на объект окна браузера
    $(window).on('scroll', function() {
        // Если записей больше нет или уже выполняется активный запрос, ничего не делаем
        if (!historyHasMore || historyIsLoading) return;

        // Вычисляем, докрутил ли пользователь страницу почти до самого конца (200px до низа)
        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 200) {
            // Блокируем новые запросы установкой флага загрузки в true
            historyIsLoading = true;
            
            // Отправляем асинхронный POST-запрос на загрузку следующей порции истории
            $.post('<?= site_url("history/load_more_history_ajax"); ?>', { offset: historyOffset }, function(response) {
                // Преобразуем строковый JSON ответ от сервера в JS объект
                let res = JSON.parse(response);
                // Если сервер сообщил об успешном выполнении запроса
                if (res.status === 'success') {
                    // Если сервер вернул непустую HTML-разметку с новыми строками
                    if (res.html && res.html.trim() !== '') {
                        // Вставляем полученные новые строки таблицы в конец контейнера tbody
                        $('#historyTableBody').append(res.html);
                        // Увеличиваем текущее смещение на размер лимита для следующего шага
                        historyOffset += historyLimit;
                    }
                    // Обновляем флаг наличия дальнейших страниц из ответа сервера
                    historyHasMore = res.has_more;
                }
                // Снимаем блокировку, возвращая флаг загрузки в false
                historyIsLoading = false;
            }).fail(function() {
                // В случае сетевой ошибки также обязательно разблокируем отправку
                historyIsLoading = false;
            });
        }
    });
</script>
