<?php
// Загружаем шаблон с рекурсивными функциями дерева задач
$this->load->view('templates/task_list_loop');
?>

<div class="relative min-h-[80vh] pb-32">
    <!-- Блок добавления корневого проекта удален и перенесен в глобальное модальное окно (body.php) -->

    <!-- Список задач -->
    <div class="flex justify-between items-end mb-4">
        <div class="flex items-center gap-6 w-1/3">
            <img src="<?= base_url('assets/img/time_tree.png') ?>" alt="Time Tree" class="w-16 h-16 object-cover rounded-2xl shadow-sm">
            <h2 class="text-3xl font-black text-gray-800"><?= lang('dash_tree_title'); ?></h2>
        </div>
        
        <!-- Кнопка добавления по центру -->
        <div class="flex-1 flex justify-center pb-2">
            <button onclick="openGlobalAddModal()" class="bg-green-600 flex items-center gap-2 hover:opacity-90 text-white font-black py-2 px-8 rounded-full text-lg shadow-lg transition-transform transform hover:scale-105 active:scale-95" title="<?= lang('btn_add'); ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                <?= lang('btn_add'); ?>
            </button>
        </div>

        <!-- Живой поиск -->
        <div class="w-1/3 flex justify-end">
            <input type="text" id="searchTaskInput" placeholder="<?= lang('dash_search_placeholder'); ?>" class="w-full max-w-sm px-5 py-3 bg-gray-50 border border-gray-200 rounded-xl text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm">
        </div>
    </div>
    
    <?php if (empty($tasks_tree)): ?>
        <div class="bg-white p-12 rounded-2xl border border-gray-200 text-center shadow-sm">
            <span class="text-6xl mb-4 block">📋</span>
            <p class="text-2xl text-gray-500"><?= lang('dash_tree_empty'); ?></p>
        </div>
    <?php else: ?>
        <div class="task-tree-root">
            <?php 
                ob_start();
                render_task_tree($tasks_tree, 1, $active_session); 
                $tree_html = ob_get_clean();
                // Раскрываем только корневой UL
                echo str_replace('hidden task-children', 'block', $tree_html);
            ?>
        </div>
        <div class="mt-8 text-center">
            <button type="button" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-6 rounded-xl shadow-sm transition-colors" onclick="$('.hidden-completed-root').slideToggle(); $(this).text( $(this).text().indexOf('<?= lang('dash_show_completed_projects') ?>') !== -1 ? '<?= lang('dash_hide_completed_projects') ?>' : '<?= lang('dash_show_completed_projects') ?>' );"><?= lang('dash_show_completed_projects'); ?></button>
        </div>
    <?php endif; ?>
</div>



<div id="editTimeModal" onclick="closeEditModal()" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div id="editModalBody" onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl p-6 md:p-8 transform transition-all max-h-[90vh] flex flex-col relative overflow-hidden">
        <!-- Кнопка закрытия -->
        <button onclick="closeEditModal()" class="absolute top-6 right-6 text-gray-400 hover:text-gray-700 transition-colors bg-white/50 backdrop-blur-md rounded-full p-2 shadow-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-xs uppercase tracking-wider font-bold mb-1 text-gray-500 flex-shrink-0"><?= lang('modal_history_title'); ?></h3>
        <p class="text-gray-800 mb-4 text-xl flex-shrink-0 truncate w-full block overflow-hidden text-ellipsis whitespace-nowrap"><?= lang('modal_edit_task'); ?> <span id="modalTaskTitle" class="font-bold"></span></p>
        
        <input type="hidden" id="modalTaskId">

        <!-- Форма добавления -->
        <div class="flex-shrink-0 mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-1"><?= lang('modal_edit_start'); ?></label>
                    <input type="datetime-local" id="modalStartTime" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-1"><?= lang('modal_edit_end'); ?></label>
                    <input type="datetime-local" id="modalEndTime" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-1"><?= lang('modal_edit_note'); ?></label>
                <input type="text" id="modalNote" placeholder="<?= lang('modal_note_placeholder'); ?>" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div class="flex gap-3">
                <button onclick="saveManualSession()" class="flex-grow bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-colors">
                    <?= lang('modal_edit_save'); ?>
                </button>
                <button onclick="closeEditModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg transition-colors">
                    <?= lang('btn_cancel'); ?>
                </button>
            </div>
        </div>

        <!-- Список последних сессий -->
        <h4 class="text-xl font-bold mb-2 text-gray-700 border-b pb-2 flex-shrink-0"><?= lang('modal_recent_sessions'); ?></h4>
        <div class="overflow-y-auto flex-grow max-h-[40vh] border border-gray-200 rounded-lg">
            <table class="w-full text-left text-sm text-gray-600 min-w-[600px]">
                <thead class="bg-gray-100 text-gray-700 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-2 w-1/4 border-b"><?= lang('modal_col_start'); ?></th>
                        <th class="px-4 py-2 w-1/4 border-b"><?= lang('modal_col_end'); ?></th>
                        <th class="px-4 py-2 w-1/6 border-b"><?= lang('modal_col_duration'); ?></th>
                        <th class="px-4 py-2 w-1/3 border-b"><?= lang('modal_col_note'); ?></th>
                        <th class="px-4 py-2 w-16 text-center border-b"></th>
                    </tr>
                </thead>
                <tbody id="modalSessionsList" class="divide-y divide-gray-100 bg-white">
                    <!-- Сюда подгружаются сессии -->
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400"><?= lang('modal_loading'); ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Панель выбора цвета (Color Picker) -->
<div id="colorPickerPopover" class="hidden absolute bg-white rounded-lg shadow-xl border border-gray-200 p-1.5 grid grid-cols-5 gap-1.5 w-max" style="background-color: #ffffff !important;">
    <button onclick="saveColor('#ef4444')" style="background-color: #ef4444;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#f97316')" style="background-color: #f97316;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#f59e0b')" style="background-color: #f59e0b;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#10b981')" style="background-color: #10b981;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#06b6d4')" style="background-color: #06b6d4;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#3b82f6')" style="background-color: #3b82f6;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#6366f1')" style="background-color: #6366f1;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#a855f7')" style="background-color: #a855f7;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#ec4899')" style="background-color: #ec4899;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('')" style="background-color: #e5e7eb;" class="w-5 h-5 rounded-full border border-gray-400 hover:scale-125 transition-transform shadow-sm flex items-center justify-center" title="<?= lang('reports_no_color'); ?>"><svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
</div>

<!-- Модальное окно для Каскадной Истории -->
<div id="cascadeHistoryModal" onclick="closeCascadeModal()" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div id="cascadeModalBody" onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl p-8 transform transition-all relative flex flex-col max-h-[90vh] md:max-h-[85vh] h-full overflow-hidden">
        <!-- Кнопка закрытия -->
        <button onclick="closeCascadeModal()" class="absolute top-6 right-6 text-gray-400 hover:text-gray-700 transition-colors bg-white/50 backdrop-blur-md rounded-full p-2 shadow-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-xs uppercase tracking-wider font-bold mb-1 text-gray-500 flex-shrink-0"><?= lang('cascade_history_title'); ?></h3>
        <p class="text-gray-800 mb-4 text-xl flex-shrink-0 truncate w-full block overflow-hidden text-ellipsis whitespace-nowrap"><span id="cascadeModalTaskTitle" class="font-bold"></span></p>
        
        <div class="mb-4 flex-shrink-0">
            <input type="text" id="cascadeSearchInput" placeholder="<?= lang('cascade_search_placeholder'); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>
        
        <div id="cascadeTableContainer" class="overflow-y-auto flex-grow border border-gray-200 rounded-lg">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-100 text-gray-700 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-4 py-2 border-b"><?= lang('modal_col_start'); ?> - <?= lang('modal_col_end'); ?></th>
                        <th class="px-4 py-2 border-b"><?= lang('cascade_col_task'); ?></th>
                        <th class="px-4 py-2 border-b"><?= lang('cascade_col_duration'); ?></th>
                        <th class="px-4 py-2 border-b"><?= lang('cascade_col_note'); ?></th>
                    </tr>
                </thead>
                <tbody id="cascadeModalSessionsList" class="divide-y divide-gray-100 bg-white">
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400"><?= lang('modal_loading'); ?></td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="mt-6 flex-shrink-0 flex justify-end">
            <button onclick="closeCascadeModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded-lg transition-colors">
                <?= lang('btn_cancel'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Модальное окно для редактирования задачи (двухколоночный макет с липкими шапкой/подвалом и Quill-редактором) -->
<div id="editTaskModal" onclick="closeEditTaskModal()" class="hidden fixed inset-0 z-[99999] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl transform transition-all relative max-h-[85vh] flex flex-col overflow-hidden">
        
        <!-- Шапка модального окна (Фиксированная сверху) -->
        <div class="p-6 border-b border-gray-100 flex justify-between items-center flex-shrink-0">
            <h3 class="text-2xl font-bold text-gray-800"><?= lang('btn_edit'); ?></h3>
            <button onclick="closeEditTaskModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <input type="hidden" id="editTaskId">
        
        <!-- Основная рабочая область формы с вертикальным скроллом -->
        <div class="p-6 overflow-y-auto flex-grow">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Левая колонка: свойства задачи -->
                <div class="space-y-4">
                    <!-- Название задачи -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('modal_title_label'); ?></label>
                        <input type="text" id="editTaskTitleInput" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    
                    <!-- Заказчик и ТЗ -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('nav_customers'); ?></label>
                            <select id="editTaskCustomer" class="customer-select w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" onchange="updateRate(this)">
                                <option value=""><?= lang('finance_no_customer'); ?></option>
                                <?php if(!empty($customers)): foreach($customers as $c): ?>
                                    <option value="<?= $c['id']; ?>" data-rate="<?= htmlspecialchars($c['default_price'] ?? '0.00'); ?>"><?= htmlspecialchars($c['name'] ?? ''); ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                        <!-- Связанное ТЗ (заполняется динамически через js) -->
                        <div id="editTaskSpecContainer" class="hidden">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Техническое задание (ТЗ)</label>
                            <select id="editTaskSpec" class="spec-select w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <option value="">Связать с ТЗ...</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Тип оплаты и цена -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('finance_type'); ?></label>
                            <select id="editTaskIsFixed" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <option value="0"><?= lang('finance_hourly'); ?></option>
                                <option value="1"><?= lang('finance_fixed'); ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('finance_price'); ?></label>
                            <input type="number" step="0.01" id="editTaskPrice" class="rate-input w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Палитра цветов для задачи -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Цвет оформления задачи</label>
                        <div class="flex flex-wrap gap-2.5 items-center bg-gray-50 border border-gray-200 rounded-xl p-3">
                            <button type="button" onclick="selectPresetColorInModal('#ef4444')" style="background-color: #ef4444;" class="modal-color-preset-btn w-6 h-6 rounded-full hover:scale-125 transition-transform shadow-sm focus:outline-none ring-blue-500 ring-offset-2" data-color="#ef4444"></button>
                            <button type="button" onclick="selectPresetColorInModal('#f97316')" style="background-color: #f97316;" class="modal-color-preset-btn w-6 h-6 rounded-full hover:scale-125 transition-transform shadow-sm focus:outline-none ring-blue-500 ring-offset-2" data-color="#f97316"></button>
                            <button type="button" onclick="selectPresetColorInModal('#f59e0b')" style="background-color: #f59e0b;" class="modal-color-preset-btn w-6 h-6 rounded-full hover:scale-125 transition-transform shadow-sm focus:outline-none ring-blue-500 ring-offset-2" data-color="#f59e0b"></button>
                            <button type="button" onclick="selectPresetColorInModal('#10b981')" style="background-color: #10b981;" class="modal-color-preset-btn w-6 h-6 rounded-full hover:scale-125 transition-transform shadow-sm focus:outline-none ring-blue-500 ring-offset-2" data-color="#10b981"></button>
                            <button type="button" onclick="selectPresetColorInModal('#06b6d4')" style="background-color: #06b6d4;" class="modal-color-preset-btn w-6 h-6 rounded-full hover:scale-125 transition-transform shadow-sm focus:outline-none ring-blue-500 ring-offset-2" data-color="#06b6d4"></button>
                            <button type="button" onclick="selectPresetColorInModal('#3b82f6')" style="background-color: #3b82f6;" class="modal-color-preset-btn w-6 h-6 rounded-full hover:scale-125 transition-transform shadow-sm focus:outline-none ring-blue-500 ring-offset-2" data-color="#3b82f6"></button>
                            <button type="button" onclick="selectPresetColorInModal('#6366f1')" style="background-color: #6366f1;" class="modal-color-preset-btn w-6 h-6 rounded-full hover:scale-125 transition-transform shadow-sm focus:outline-none ring-blue-500 ring-offset-2" data-color="#6366f1"></button>
                            <button type="button" onclick="selectPresetColorInModal('#a855f7')" style="background-color: #a855f7;" class="modal-color-preset-btn w-6 h-6 rounded-full hover:scale-125 transition-transform shadow-sm focus:outline-none ring-blue-500 ring-offset-2" data-color="#a855f7"></button>
                            <button type="button" onclick="selectPresetColorInModal('#ec4899')" style="background-color: #ec4899;" class="modal-color-preset-btn w-6 h-6 rounded-full hover:scale-125 transition-transform shadow-sm focus:outline-none ring-blue-500 ring-offset-2" data-color="#ec4899"></button>
                            <button type="button" onclick="selectPresetColorInModal('')" style="background-color: #e5e7eb;" class="modal-color-preset-btn w-6 h-6 rounded-full border border-gray-400 hover:scale-125 transition-transform shadow-sm flex items-center justify-center focus:outline-none ring-blue-500 ring-offset-2" data-color="" title="<?= lang('reports_no_color'); ?>">
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                            <input type="hidden" id="editTaskColor" value="">
                        </div>
                    </div>
                </div>
                
                <!-- Правая колонка: WYSIWYG редактор Quill для описания -->
                <div class="flex flex-col h-full min-h-[320px] md:min-h-0">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Детальное описание задачи / требований</label>
                    <div id="editTaskDescriptionContainer" class="flex-grow min-h-[260px] md:h-auto bg-gray-50 border border-gray-200 rounded-xl overflow-hidden flex flex-col">
                        <div id="editTaskDescriptionEditor" class="flex-grow bg-white"></div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Подвал модального окна (Фиксированный снизу) -->
        <div class="p-6 border-t border-gray-100 flex justify-end gap-3 flex-shrink-0 bg-gray-50 rounded-b-3xl">
            <button onclick="closeEditTaskModal()" class="px-6 py-3 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold rounded-xl transition-colors text-sm">
                <?= lang('btn_cancel'); ?>
            </button>
            <button onclick="saveTaskTitle()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg transition-colors text-sm">
                <?= lang('btn_save'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Подключаем Quill WYSIWYG редактор для красивого оформления задач -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<script src="<?= base_url('assets/js/tasks.js?v=' . time()) ?>"></script>
