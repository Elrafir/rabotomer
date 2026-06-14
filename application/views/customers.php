<!-- 
  Раздел: Заказчики и Технические Задания (ТЗ).
  Предоставляет двухколоночный интерфейс:
  - Левая колонка: навигация по списку заказчиков и кнопка добавления.
  - Правая колонка: основная рабочая зона по выбранному заказчику (информация, дерево задач, ТЗ и файлы).
-->
<div class="w-full h-full flex flex-col gap-6">
    <!-- Шапка раздела -->
    <div class="flex justify-between items-end mb-2 flex-shrink-0">
        <div class="flex items-center gap-6">
            <img src="<?= base_url('assets/img/customers_logo.png') ?>" alt="Customers Logo" class="w-16 h-16 object-cover rounded-2xl shadow-sm">
            <h2 class="text-3xl font-black text-gray-800"><?= lang('cust_title_and_spec'); ?></h2>
        </div>
    </div>

    <div class="flex-grow flex flex-col md:flex-row gap-6 overflow-hidden min-h-[500px]">
        <!-- ЛЕВАЯ КОЛОНКА: Навигационный сайдбар со списком заказчиков -->
        <div class="w-full md:w-1/4 flex flex-col bg-white rounded-3xl shadow-sm border border-gray-100 p-4 overflow-hidden flex-shrink-0">
            <div class="mb-4">
                <button onclick="openAddCustomerModal()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-colors flex items-center justify-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <?= lang('customers_new'); ?>
                </button>
            </div>
            
            <h3 class="text-xs uppercase tracking-wider font-bold mb-2 text-gray-400 pl-2"><?= lang('cust_list_title'); ?></h3>
            <div id="customersSidebarList" class="flex-grow overflow-y-auto space-y-1 pr-1">
                <?php if (empty($customers)): ?>
                    <div class="text-center text-gray-400 py-8 text-xs italic"><?= lang('cust_empty_list'); ?></div>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <a href="<?= site_url('customers/index/'.$c['id']) ?>" class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl transition-colors text-sm <?= $c['id'] == $active_customer_id ? 'bg-blue-50 text-blue-600 font-bold border-l-4 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <span class="truncate"><?= htmlspecialchars($c['name']) ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ПРАВАЯ КОЛОНКА: Детальная информация по выбранному заказчику -->
        <div class="flex-grow w-full md:w-3/4 flex flex-col overflow-y-auto pb-32 pr-2">
            <?php if (empty($active_customer)): ?>
                <!-- Заглушка, если заказчиков еще нет вообще -->
                <div class="bg-white p-12 rounded-3xl border border-gray-100 text-center shadow-sm my-auto">
                    <span class="text-6xl mb-4 block text-gray-300">👥</span>
                    <h3 class="text-xl font-bold text-gray-700 mb-2"><?= lang('cust_no_customers_title'); ?></h3>
                    <p class="text-gray-500 mb-6"><?= lang('cust_no_customers_desc'); ?></p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <!-- Шапка активного заказчика с действиями -->
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h3 class="text-2xl font-black text-gray-800 mb-1"><?= htmlspecialchars($active_customer['name']) ?></h3>
                            <button onclick="openCustomerInfoModal()" class="text-blue-600 hover:underline text-sm font-semibold flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <?= lang('cust_info_btn'); ?>
                            </button>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="openEditCustomerModal(<?= $active_customer['id']; ?>, '<?= htmlspecialchars(addslashes($active_customer['name'] ?? ''), ENT_QUOTES); ?>', '<?= htmlspecialchars(addslashes($active_customer['notes'] ?? ''), ENT_QUOTES); ?>', '<?= $active_customer['default_price'] ?>', '<?= $active_customer['default_prepayment'] ?>', '<?= $active_customer['default_payment_type'] ?>')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-xl shadow-sm transition-colors text-sm flex items-center gap-2">
                                ✏️ <?= lang('btn_edit'); ?>
                            </button>
                            <a href="<?= site_url('customers/delete/'.$active_customer['id']); ?>" onclick="return confirm('<?= htmlspecialchars(lang('cust_delete_confirm'), ENT_QUOTES); ?>');" class="bg-red-50 hover:bg-red-100 text-red-600 font-bold py-2.5 px-4 rounded-xl shadow-sm transition-colors text-sm flex items-center gap-2">
                                🗑️ <?= lang('btn_delete'); ?>
                            </a>
                        </div>
                    </div>

                    <!-- Блок Дерева Задач этого заказчика -->
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                        <h4 class="text-lg font-black text-gray-800 mb-4 flex items-center gap-2">
                            🗂️ <?= lang('cust_linked_tasks'); ?>
                        </h4>
                        
                        <?php if (empty($customer_tasks_tree)): ?>
                            <p class="text-sm text-gray-400 italic"><?= lang('cust_no_tasks'); ?></p>
                        <?php else: ?>
                            <div class="max-h-72 overflow-y-auto pr-2 space-y-2">
                                <?php 
                                    // Рекурсивный вывод дерева задач
                                    if (!function_exists('render_customer_task_tree')) {
                                        function render_customer_task_tree($tasks, $level = 1) {
                                            if (empty($tasks) || $level > 3) return;
                                            echo '<ul class="space-y-2 ' . ($level > 1 ? 'ml-4 pl-4 border-l-2 border-gray-200 mt-2' : '') . '">';
                                            foreach ($tasks as $task) {
                                                $has_children = !empty($task['children']);
                                                $color_dot = !empty($task['color']) ? "background-color: {$task['color']};" : "background-color: #e5e7eb;";
                                                echo '<li class="p-3 bg-gray-50 border border-gray-100 rounded-xl flex flex-col gap-1">';
                                                echo '<div class="flex justify-between items-center">';
                                                echo '<div class="flex items-center gap-2">';
                                                echo '<div class="w-3 h-3 rounded-full border border-gray-200 shadow-sm" style="' . $color_dot . '"></div>';
                                                echo '<span class="font-semibold text-gray-800 text-sm">' . htmlspecialchars($task['title']) . '</span>';
                                                echo '</div>';
                                                echo '<span class="text-xs font-bold text-gray-500 font-mono bg-white px-2 py-0.5 rounded-full border">' . $task['formatted_time'] . '</span>';
                                                echo '</div>';
                                                if ($has_children) {
                                                    render_customer_task_tree($task['children'], $level + 1);
                                                }
                                                echo '</li>';
                                            }
                                            echo '</ul>';
                                        }
                                    }
                                    render_customer_task_tree($customer_tasks_tree);
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Раздел ТЗ (Технических заданий) -->
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <h4 class="text-lg font-black text-gray-800 flex items-center gap-2">
                                📝 <?= lang('cust_specs_title'); ?>
                            </h4>
                            <button onclick="openAddSpecModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-xl shadow transition-colors text-xs flex items-center gap-1">
                                ➕ <?= lang('cust_create_spec_btn'); ?>
                            </button>
                        </div>

                        <?php if (empty($specs)): ?>
                            <div class="text-center py-12 text-gray-400 italic text-sm"><?= lang('cust_no_specs'); ?></div>
                        <?php else: ?>
                            <div class="space-y-6">
                                <?php foreach ($specs as $spec): ?>
                                    <div class="border border-gray-100 rounded-2xl p-5 hover:shadow-md transition-shadow relative">
                                        <div class="flex justify-between items-start mb-3 border-b border-gray-50 pb-3">
                                            <div>
                                                <h5 class="text-base font-black text-gray-800"><?= htmlspecialchars($spec['title']) ?></h5>
                                                <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-xs text-gray-500">
                                                    <span><?= lang('cust_created_at'); ?> <?= date('d.m.Y H:i', strtotime($spec['created_at'])) ?></span>
                                                    <span>💰 <?= lang('cust_price_badge'); ?> <strong><?= number_format($spec['price'], 2, '.', ' ') ?> руб.</strong></span>
                                                    <span>💳 <?= lang('cust_prepayment_badge'); ?> <strong><?= number_format($spec['prepayment'], 2, '.', ' ') ?> руб.</strong></span>
                                                    <span>🛠️ <?= lang('cust_payment_badge'); ?> <strong><?= format_payment_type($spec['payment_type']) ?></strong></span>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <button onclick="openEditSpecModal(<?= $spec['id'] ?>, '<?= htmlspecialchars(addslashes($spec['title']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($spec['content']), ENT_QUOTES) ?>', '<?= $spec['price'] ?>', '<?= $spec['prepayment'] ?>', '<?= $spec['payment_type'] ?>', <?= json_encode($spec['linked_task_ids'] ?? []) ?>)" class="text-gray-400 hover:text-blue-600 transition-colors p-1" title="<?= htmlspecialchars(lang('btn_edit'), ENT_QUOTES); ?>">
                                                    ✏️
                                                </button>
                                                <a href="<?= site_url('customers/delete_spec/'.$spec['id']) ?>" onclick="return confirm('<?= htmlspecialchars(lang('cust_delete_spec_confirm'), ENT_QUOTES); ?>');" class="text-gray-400 hover:text-red-600 transition-colors p-1" title="<?= htmlspecialchars(lang('btn_delete'), ENT_QUOTES); ?>">
                                                    🗑️
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Текст ТЗ (Рендерим HTML) -->
                                        <div class="text-gray-700 text-sm leading-relaxed mb-4 prose max-w-none">
                                            <?= $spec['content'] ?>
                                        </div>

                                        <!-- Привязанные задачи -->
                                        <div class="mb-4">
                                            <span class="text-xs font-bold text-gray-400 uppercase"><?= lang('cust_spec_linked_tasks'); ?></span>
                                            <div class="flex flex-wrap gap-2 mt-1">
                                                <?php 
                                                $linked_tasks_found = false;
                                                foreach ($customer_tasks as $task) {
                                                    if (in_array($task['id'], $spec['linked_task_ids'] ?? [])) {
                                                        $linked_tasks_found = true;
                                                        $color = !empty($task['color']) ? $task['color'] : '#e5e7eb';
                                                        echo '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border" style="border-left-color: ' . $color . '; border-left-width: 4px;">' . htmlspecialchars($task['title']) . '</span>';
                                                    }
                                                }
                                                if (!$linked_tasks_found) {
                                                    echo '<span class="text-xs text-gray-400 italic">' . lang('cust_spec_no_linked_tasks') . '</span>';
                                                }
                                                ?>
                                            </div>
                                        </div>

                                        <!-- Вложения файлов -->
                                        <div class="bg-gray-50 p-4 rounded-xl">
                                            <h6 class="text-xs uppercase font-bold text-gray-400 mb-2"><?= lang('cust_attached_files_title'); ?></h6>
                                            <div id="file-list-<?= $spec['id'] ?>" class="flex flex-wrap gap-2 mb-3">
                                                <?php if (empty($spec['files'])): ?>
                                                    <span class="text-xs text-gray-400 italic empty-files-label"><?= lang('cust_no_files'); ?></span>
                                                <?php else: ?>
                                                    <?php foreach ($spec['files'] as $f): ?>
                                                        <div id="file-item-<?= $f['id'] ?>" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 text-xs shadow-sm">
                                                            <span><?= get_file_icon_emoji($f['orig_name'], $f['is_link']) ?></span>
                                                            <?php if ($f['is_link']): ?>
                                                                <a href="<?= htmlspecialchars($f['filename']) ?>" target="_blank" class="text-blue-600 hover:underline font-medium"><?= htmlspecialchars($f['orig_name']) ?></a>
                                                                <span class="text-gray-400 font-mono">(<?= lang('cust_file_link'); ?>)</span>
                                                            <?php else: ?>
                                                                <span class="text-gray-700 font-medium"><?= htmlspecialchars($f['orig_name']) ?></span>
                                                                <span class="text-gray-400 font-mono">(<?= round($f['file_size']/1024, 1) ?> KB)</span>
                                                                <a href="<?= site_url('customers/download_file/'.$f['id']) ?>" class="text-blue-500 hover:text-blue-700" title="<?= htmlspecialchars(lang('cust_download_title'), ENT_QUOTES); ?>">📥</a>
                                                            <?php endif; ?>
                                                            <button onclick="deleteSpecFile(<?= $f['id'] ?>)" class="text-red-400 hover:text-red-600" title="<?= htmlspecialchars(lang('btn_delete'), ENT_QUOTES); ?>">✖</button>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Драг-н-дроп зона загрузки -->
                                            <input type="file" id="file-input-<?= $spec['id'] ?>" class="hidden" multiple onchange="handleFileSelect(event, <?= $spec['id'] ?>)">
                                            <div class="border-2 border-dashed border-gray-300 hover:border-blue-400 transition-colors rounded-xl p-4 text-center cursor-pointer relative"
                                                 ondragover="event.preventDefault(); $(this).addClass('border-blue-400')"
                                                 ondragleave="$(this).removeClass('border-blue-400')"
                                                 ondrop="handleFileDrop(event, <?= $spec['id'] ?>)"
                                                 onclick="if(event.target.tagName.toLowerCase() !== 'input') { document.getElementById('file-input-<?= $spec['id'] ?>').click(); }">
                                                 <span class="text-xs text-gray-500"><?= lang('cust_dropzone_text'); ?> <span class="text-blue-500 font-bold"><?= lang('cust_dropzone_select'); ?></span></span>
                                                 <div id="upload-progress-container-<?= $spec['id'] ?>" class="hidden absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center rounded-xl p-4">
                                                     <div class="w-full bg-gray-200 rounded-full h-2">
                                                         <div id="upload-progress-<?= $spec['id'] ?>" class="bg-blue-600 h-2 rounded-full" style="width: 0%"></div>
                                                     </div>
                                                 </div>
                                            </div>

                                            <!-- Добавление внешних ссылок и загрузка по URL -->
                                            <div class="mt-4 pt-4 border-t border-gray-100 flex flex-col sm:flex-row gap-2">
                                                <input type="text" id="url-input-<?= $spec['id'] ?>" class="flex-grow px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="https://example.com/file.pdf...">
                                                <input type="text" id="url-title-<?= $spec['id'] ?>" class="w-full sm:w-1/4 px-3 py-2 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="<?= htmlspecialchars(lang('cust_file_link'), ENT_QUOTES); ?>">
                                                <button onclick="attachLink(<?= $spec['id'] ?>)" class="bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold py-2 px-3 rounded-xl text-xs transition-colors flex items-center justify-center gap-1">
                                                    🔗 <?= lang('cust_link_btn'); ?>
                                                </button>
                                                <button onclick="downloadFromUrl(<?= $spec['id'] ?>)" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-3 rounded-xl text-xs transition-colors flex items-center justify-center gap-1">
                                                    📥 <?= lang('cust_download_btn'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Модальное окно добавления заказчика -->
<div id="addCustomerModal" onclick="closeAddCustomerModal()" class="hidden fixed inset-0 z-[120] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 transform transition-all relative">
        <button type="button" onclick="closeAddCustomerModal()" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-2xl font-bold mb-6 text-gray-800"><?= lang('customers_new'); ?></h3>
        
        <?php echo form_open('customers/add', ['class' => 'space-y-4']); ?>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_name_label'); ?></label>
                <input type="text" name="name" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_notes_label'); ?></label>
                <textarea name="notes" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="<?= htmlspecialchars(lang('cust_notes_placeholder'), ENT_QUOTES); ?>"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_default_price_label'); ?></label>
                    <input type="number" name="default_price" step="0.01" min="0" value="0.00" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_default_prepayment_label'); ?></label>
                    <input type="number" name="default_prepayment" step="0.01" min="0" value="0.00" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_default_payment_type_label'); ?></label>
                <select name="default_payment_type" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="hourly"><?= lang('finance_hourly'); ?></option>
                    <option value="fixed"><?= lang('finance_fixed'); ?></option>
                </select>
            </div>
            <button type="submit" class="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-colors">
                <?= lang('btn_create'); ?>
            </button>
        <?php echo form_close(); ?>
    </div>
</div>

<!-- Модальное окно редактирования заказчика -->
<div id="editCustomerModal" onclick="closeEditCustomerModal()" class="hidden fixed inset-0 z-[120] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 transform transition-all relative">
        <button type="button" onclick="closeEditCustomerModal()" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-2xl font-bold mb-6 text-gray-800"><?= lang('cust_edit_customer_title'); ?></h3>
        
        <?php echo form_open('customers/edit', ['class' => 'space-y-4']); ?>
            <input type="hidden" name="id" id="editCustomerId">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_name_label'); ?></label>
                <input type="text" name="name" id="editCustomerName" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_notes_label'); ?></label>
                <textarea name="notes" id="editCustomerNotes" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_default_price_label'); ?></label>
                    <input type="number" name="default_price" id="editCustomerDefaultPrice" step="0.01" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_default_prepayment_label'); ?></label>
                    <input type="number" name="default_prepayment" id="editCustomerDefaultPrepayment" step="0.01" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_default_payment_type_label'); ?></label>
                <select name="default_payment_type" id="editCustomerDefaultPaymentType" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="hourly"><?= lang('finance_hourly'); ?></option>
                    <option value="fixed"><?= lang('finance_fixed'); ?></option>
                </select>
            </div>
            <button type="submit" class="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-colors">
                <?= lang('btn_save'); ?>
            </button>
        <?php echo form_close(); ?>
    </div>
</div>

<!-- Модальное окно просмотра информации (Инфо) -->
<?php if (!empty($active_customer)): ?>
<div id="customerInfoModal" onclick="closeCustomerInfoModal()" class="hidden fixed inset-0 z-[120] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 transform transition-all relative">
        <button type="button" onclick="closeCustomerInfoModal()" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-2xl font-bold mb-4 text-gray-800"><?= lang('cust_details_title'); ?></h3>
        <div class="border-t border-gray-100 pt-4 space-y-4">
            <div>
                <span class="text-xs uppercase font-bold text-gray-400"><?= lang('cust_name_label'); ?></span>
                <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($active_customer['name']) ?></p>
            </div>
            <div>
                <span class="text-xs uppercase font-bold text-gray-400"><?= lang('cust_notes_label'); ?></span>
                <p class="text-sm text-gray-600 whitespace-pre-wrap leading-relaxed"><?= !empty($active_customer['notes']) ? htmlspecialchars($active_customer['notes']) : lang('cust_no_notes') ?></p>
            </div>
            <div class="grid grid-cols-2 gap-4 border-t border-gray-100 pt-4">
                <div>
                    <span class="text-xs uppercase font-bold text-gray-400"><?= lang('cust_default_price_label'); ?></span>
                    <p class="text-sm font-bold text-gray-800"><?= number_format($active_customer['default_price'], 2, '.', ' ') ?> руб.</p>
                </div>
                <div>
                    <span class="text-xs uppercase font-bold text-gray-400"><?= lang('cust_default_prepayment_label'); ?></span>
                    <p class="text-sm font-bold text-gray-800"><?= number_format($active_customer['default_prepayment'], 2, '.', ' ') ?> руб.</p>
                </div>
            </div>
            <div>
                <span class="text-xs uppercase font-bold text-gray-400"><?= lang('cust_default_payment_type_label'); ?></span>
                <p class="text-sm font-bold text-gray-800"><?= format_payment_type($active_customer['default_payment_type']) ?></p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Модальное окно создания ТЗ -->
<div id="addSpecModal" onclick="closeAddSpecModal()" class="hidden fixed inset-0 z-[120] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl p-8 transform transition-all relative max-h-[90vh] flex flex-col overflow-y-auto">
        <button type="button" onclick="closeAddSpecModal()" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-2xl font-bold mb-6 text-gray-800"><?= lang('cust_new_spec_title'); ?></h3>
        
        <?php echo form_open('customers/add_spec', ['id' => 'addSpecForm', 'class' => 'space-y-4 flex flex-col flex-grow']); ?>
            <input type="hidden" name="customer_id" value="<?= $active_customer_id ?>">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_spec_title_label'); ?></label>
                <input type="text" name="title" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required placeholder="<?= htmlspecialchars(lang('cust_spec_title_placeholder'), ENT_QUOTES); ?>">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_price_label'); ?></label>
                    <input type="number" name="price" step="0.01" min="0" value="<?= !empty($active_customer) ? htmlspecialchars($active_customer['default_price']) : '0.00' ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_prepayment_label'); ?></label>
                    <input type="number" name="prepayment" step="0.01" min="0" value="<?= !empty($active_customer) ? htmlspecialchars($active_customer['default_prepayment']) : '0.00' ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_payment_type_label'); ?></label>
                <select name="payment_type" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="hourly" <?= (!empty($active_customer) && $active_customer['default_payment_type'] == 'hourly') ? 'selected' : '' ?>><?= lang('finance_hourly'); ?></option>
                    <option value="fixed" <?= (!empty($active_customer) && $active_customer['default_payment_type'] == 'fixed') ? 'selected' : '' ?>><?= lang('finance_fixed'); ?></option>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_link_tasks_label'); ?></label>
                <div class="max-h-40 overflow-y-auto border border-gray-200 rounded-xl p-3 bg-gray-50 space-y-2">
                    <?php if (empty($customer_tasks)): ?>
                        <p class="text-xs text-gray-400 italic"><?= lang('cust_no_tasks_available'); ?></p>
                    <?php else: ?>
                        <?php foreach ($customer_tasks as $task): ?>
                            <label class="flex items-center gap-2 text-sm cursor-pointer p-1 hover:bg-gray-100 rounded">
                                <input type="checkbox" name="linked_tasks[]" value="<?= $task['id'] ?>" class="rounded text-blue-600 focus:ring-blue-500">
                                <span><?= htmlspecialchars($task['title']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex-grow flex flex-col">
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_spec_content_label'); ?></label>
                
                <!-- Контейнер для Quill WYSIWYG -->
                <div id="add-editor-container" class="h-64 bg-gray-50 border border-gray-200 rounded-xl overflow-hidden flex flex-col">
                    <div id="add-editor" class="flex-grow bg-white"></div>
                </div>
                <input type="hidden" name="content" id="addSpecContent">
            </div>
            
            <button type="submit" onclick="submitAddSpecForm(event)" class="w-full mt-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-colors">
                <?= lang('btn_save'); ?> ТЗ
            </button>
        <?php echo form_close(); ?>
    </div>
</div>

<!-- Модальное окно редактирования ТЗ -->
<div id="editSpecModal" onclick="closeEditSpecModal()" class="hidden fixed inset-0 z-[120] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl p-8 transform transition-all relative max-h-[90vh] flex flex-col overflow-y-auto">
        <button type="button" onclick="closeEditSpecModal()" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-2xl font-bold mb-6 text-gray-800"><?= lang('cust_edit_spec_title'); ?></h3>
        
        <?php echo form_open('customers/edit_spec', ['id' => 'editSpecForm', 'class' => 'space-y-4 flex flex-col flex-grow']); ?>
            <input type="hidden" name="customer_id" value="<?= $active_customer_id ?>">
            <input type="hidden" name="spec_id" id="editSpecId">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_spec_title_label'); ?></label>
                <input type="text" name="title" id="editSpecTitle" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_price_label'); ?></label>
                    <input type="number" name="price" id="editSpecPrice" step="0.01" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_prepayment_label'); ?></label>
                    <input type="number" name="prepayment" id="editSpecPrepayment" step="0.01" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_payment_type_label'); ?></label>
                <select name="payment_type" id="editSpecPaymentType" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="hourly"><?= lang('finance_hourly'); ?></option>
                    <option value="fixed"><?= lang('finance_fixed'); ?></option>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_link_tasks_label'); ?></label>
                <div class="max-h-40 overflow-y-auto border border-gray-200 rounded-xl p-3 bg-gray-50 space-y-2">
                    <?php if (empty($customer_tasks)): ?>
                        <p class="text-xs text-gray-400 italic"><?= lang('cust_no_tasks_available'); ?></p>
                    <?php else: ?>
                        <?php foreach ($customer_tasks as $task): ?>
                            <label class="flex items-center gap-2 text-sm cursor-pointer p-1 hover:bg-gray-100 rounded">
                                <input type="checkbox" name="linked_tasks[]" value="<?= $task['id'] ?>" class="edit-spec-task-checkbox rounded text-blue-600 focus:ring-blue-500">
                                <span><?= htmlspecialchars($task['title']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex-grow flex flex-col">
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_spec_content_label'); ?></label>
                
                <!-- Контейнер для Quill WYSIWYG -->
                <div id="edit-editor-container" class="h-64 bg-gray-50 border border-gray-200 rounded-xl overflow-hidden flex flex-col">
                    <div id="edit-editor" class="flex-grow bg-white"></div>
                </div>
                <input type="hidden" name="content" id="editSpecContent">
            </div>
            
            <button type="submit" onclick="submitEditSpecForm(event)" class="w-full mt-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-colors">
                <?= lang('btn_save'); ?> изменения
            </button>
        <?php echo form_close(); ?>
    </div>
</div>

<!-- Подключаем Quill WYSIWYG редактор для красивого оформления ТЗ -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<script>
    // Инициализируем глобальные объекты редакторов
    var addQuill = null;
    var editQuill = null;

    $(document).ready(function() {
        // Проверяем доступность библиотеки Quill перед инициализацией
        if (typeof Quill !== 'undefined') {
            var options = {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['clean']
                    ]
                }
            };
            addQuill = new Quill('#add-editor', options);
            editQuill = new Quill('#edit-editor', options);
        } else {
            // Если Quill недоступен (например, нет интернета), заменяем блоки простыми textarea
            $('#add-editor-container').html('<textarea id="add-editor-fallback" class="w-full h-full p-4 border border-gray-200 rounded-xl text-sm focus:outline-none" placeholder="<?= htmlspecialchars(lang('cust_spec_desc_placeholder'), ENT_QUOTES); ?>"></textarea>');
            $('#edit-editor-container').html('<textarea id="edit-editor-fallback" class="w-full h-full p-4 border border-gray-200 rounded-xl text-sm focus:outline-none"></textarea>');
        }
    });

    // --- Модальное окно добавления заказчика ---
    function openAddCustomerModal() {
        $('#addCustomerModal').removeClass('hidden');
    }
    function closeAddCustomerModal() {
        $('#addCustomerModal').addClass('hidden');
    }

    // --- Модальное окно редактирования заказчика ---
    function openEditCustomerModal(id, name, notes, defaultPrice, defaultPrepayment, defaultPaymentType) {
        $('#editCustomerId').val(id);
        $('#editCustomerName').val(name);
        $('#editCustomerNotes').val(notes);
        $('#editCustomerDefaultPrice').val(defaultPrice || '0.00');
        $('#editCustomerDefaultPrepayment').val(defaultPrepayment || '0.00');
        $('#editCustomerDefaultPaymentType').val(defaultPaymentType || 'hourly');
        $('#editCustomerModal').removeClass('hidden');
    }
    function closeEditCustomerModal() {
        $('#editCustomerModal').addClass('hidden');
    }

    // --- Модальное окно Инфо заказчика ---
    function openCustomerInfoModal() {
        $('#customerInfoModal').removeClass('hidden');
    }
    function closeCustomerInfoModal() {
        $('#customerInfoModal').addClass('hidden');
    }

    // --- Модальные окна ТЗ ---
    function openAddSpecModal() {
        if (addQuill) {
            addQuill.setContents([]);
        } else {
            $('#add-editor-fallback').val('');
        }
        $('#addSpecModal').removeClass('hidden');
    }
    function closeAddSpecModal() {
        $('#addSpecModal').addClass('hidden');
    }

    function openEditSpecModal(id, title, content, price, prepayment, paymentType, linkedTaskIds) {
        $('#editSpecId').val(id);
        $('#editSpecTitle').val(title);
        $('#editSpecPrice').val(price || '0.00');
        $('#editSpecPrepayment').val(prepayment || '0.00');
        $('#editSpecPaymentType').val(paymentType || 'hourly');

        // Снимаем отметки со всех чекбоксов
        $('.edit-spec-task-checkbox').prop('checked', false);
        // Отмечаем привязанные
        if (linkedTaskIds && Array.isArray(linkedTaskIds)) {
            linkedTaskIds.forEach(function(taskId) {
                $('.edit-spec-task-checkbox[value="' + taskId + '"]').prop('checked', true);
            });
        }

        if (editQuill) {
            editQuill.clipboard.dangerouslyPasteHTML(content);
        } else {
            $('#edit-editor-fallback').val(content);
        }
        $('#editSpecModal').removeClass('hidden');
    }
    function closeEditSpecModal() {
        $('#editSpecModal').addClass('hidden');
    }

    // Перенос содержимого WYSIWYG в input перед сабмитом добавления ТЗ
    function submitAddSpecForm(e) {
        var content = '';
        if (addQuill) {
            content = addQuill.root.innerHTML;
            if (addQuill.getText().trim() === '') content = '';
        } else {
            content = $('#add-editor-fallback').val();
        }
        $('#addSpecContent').val(content);
    }

    // Перенос содержимого WYSIWYG в input перед сабмитом изменения ТЗ
    function submitEditSpecForm(e) {
        var content = '';
        if (editQuill) {
            content = editQuill.root.innerHTML;
            if (editQuill.getText().trim() === '') content = '';
        } else {
            content = $('#edit-editor-fallback').val();
        }
        $('#editSpecContent').val(content);
    }

    // --- AJAX ЗАГРУЗКА И УДАЛЕНИЕ ФАЙЛОВ ТЗ ---

    // Обработка drag-and-drop
    function handleFileDrop(e, specId) {
        e.preventDefault();
        $(e.currentTarget).removeClass('border-blue-400');
        var files = e.dataTransfer.files;
        if (files.length > 0) {
            uploadFiles(files, specId);
        }
    }

    // Обработка выбора файлов на диске
    function handleFileSelect(e, specId) {
        var files = e.target.files;
        if (files.length > 0) {
            uploadFiles(files, specId);
        }
    }

    // Загрузка файлов на сервер через FormData и AJAX
    function uploadFiles(files, specId) {
        var formData = new FormData();
        formData.append('spec_id', specId);
        
        // Закачиваем первый выбранный файл в итерации (поддержка мульти-загрузки последовательно)
        formData.append('file', files[0]);

        var progressContainer = $('#upload-progress-container-' + specId);
        var progressBar = $('#upload-progress-' + specId);
        
        progressContainer.removeClass('hidden');
        progressBar.css('width', '0%');

        $.ajax({
            url: '<?= site_url("customers/upload_file") ?>',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        progressBar.css('width', percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                progressContainer.addClass('hidden');
                try {
                    var res = JSON.parse(response);
                    if (res.status === 'success') {
                        // Очищаем метку "Нет файлов", если она есть
                        var fileList = $('#file-list-' + specId);
                        fileList.find('.empty-files-label').remove();

                        // Добавляем новый файл в DOM
                        var sizeKb = Math.round(files[0].size / 102.4) / 10;
                        var fileHtml = `
                            <div id="file-item-${res.file_id}" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 text-xs shadow-sm">
                                <span>${res.icon}</span>
                                <span class="text-gray-700 font-medium">${res.orig_name}</span>
                                <span class="text-gray-400 font-mono">(${sizeKb} KB)</span>
                                <a href="${res.download_url}" class="text-blue-500 hover:text-blue-700" title="<?= htmlspecialchars(lang('cust_download_title'), ENT_QUOTES); ?>">📥</a>
                                <button onclick="deleteSpecFile(${res.file_id})" class="text-red-400 hover:text-red-600" title="<?= htmlspecialchars(lang('btn_delete'), ENT_QUOTES); ?>">✖</button>
                            </div>
                        `;
                        fileList.append(fileHtml);

                        // Если загрузили несколько файлов, отправляем следующий рекурсивно
                        if (files.length > 1) {
                            var remainingFiles = Array.prototype.slice.call(files, 1);
                            uploadFiles(remainingFiles, specId);
                        }
                    } else {
                        alert('Ошибка при загрузке: ' + res.message);
                    }
                } catch (err) {
                    console.error(err);
                    alert('Произошла системная ошибка при загрузке файла');
                }
            },
            error: function() {
                progressContainer.addClass('hidden');
                alert('Ошибка отправки файла на сервер');
            }
        });
    }

    // Удаление прикрепленного файла ТЗ через AJAX
    function deleteSpecFile(fileId) {
        if (!confirm('<?= htmlspecialchars(lang('js_confirm_delete_file'), ENT_QUOTES); ?>')) {
            return;
        }

        $.post('<?= site_url("customers/delete_file/") ?>' + fileId, {}, function(response) {
            try {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    var item = $('#file-item-' + fileId);
                    var parent = item.parent();
                    item.remove();
                    
                    // Если файлов больше нет, вставляем плейсхолдер
                    if (parent.children().length === 0) {
                        parent.html('<span class="text-xs text-gray-400 italic empty-files-label"><?= lang('cust_no_files'); ?></span>');
                    }
                } else {
                    alert(res.message);
                }
            } catch(e) {
                console.error(e);
                alert('Произошла ошибка при удалении файла');
            }
        });
    }

    // Прикрепить внешнюю ссылку
    function attachLink(specId) {
        var urlInput = $('#url-input-' + specId);
        var titleInput = $('#url-title-' + specId);
        var url = urlInput.val().trim();
        var title = titleInput.val().trim();

        if (url === '') {
            alert('<?= htmlspecialchars(lang('js_err_enter_url'), ENT_QUOTES); ?>');
            return;
        }

        $.post('<?= site_url("customers/add_link_ajax") ?>', { spec_id: specId, url: url, title: title }, function(response) {
            try {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    var fileList = $('#file-list-' + specId);
                    fileList.find('.empty-files-label').remove();

                    var itemHtml = `
                        <div id="file-item-${res.file_id}" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 text-xs shadow-sm">
                            <span>${res.icon}</span>
                            <a href="${res.url}" target="_blank" class="text-blue-600 hover:underline font-medium">${res.orig_name}</a>
                            <span class="text-gray-400 font-mono">(<?= lang('cust_file_link'); ?>)</span>
                            <button onclick="deleteSpecFile(${res.file_id})" class="text-red-400 hover:text-red-600" title="<?= htmlspecialchars(lang('btn_delete'), ENT_QUOTES); ?>">✖</button>
                        </div>
                    `;
                    fileList.append(itemHtml);
                    urlInput.val('');
                    titleInput.val('');
                } else {
                    alert(res.message);
                }
            } catch(e) {
                console.error(e);
                alert('<?= htmlspecialchars(lang('js_err_add_link_fail'), ENT_QUOTES); ?>');
            }
        });
    }

    // Скачать файл по URL и сохранить его на сервере
    function downloadFromUrl(specId) {
        var urlInput = $('#url-input-' + specId);
        var url = urlInput.val().trim();

        if (url === '') {
            alert('<?= htmlspecialchars(lang('js_err_enter_url_download'), ENT_QUOTES); ?>');
            return;
        }

        var progressContainer = $('#upload-progress-container-' + specId);
        var progressBar = $('#upload-progress-' + specId);
        
        progressContainer.removeClass('hidden');
        progressBar.css('width', '100%');

        $.post('<?= site_url("customers/download_from_url_ajax") ?>', { spec_id: specId, url: url }, function(response) {
            progressContainer.addClass('hidden');
            try {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    var fileList = $('#file-list-' + specId);
                    fileList.find('.empty-files-label').remove();

                    var sizeKb = Math.round(res.file_size / 102.4) / 10;
                    var itemHtml = `
                        <div id="file-item-${res.file_id}" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 text-xs shadow-sm">
                            <span>${res.icon}</span>
                            <span class="text-gray-700 font-medium">${res.orig_name}</span>
                            <span class="text-gray-400 font-mono">(${sizeKb} KB)</span>
                            <a href="${res.download_url}" class="text-blue-500 hover:text-blue-700" title="<?= htmlspecialchars(lang('cust_download_title'), ENT_QUOTES); ?>">📥</a>
                            <button onclick="deleteSpecFile(${res.file_id})" class="text-red-400 hover:text-red-600" title="<?= htmlspecialchars(lang('btn_delete'), ENT_QUOTES); ?>">✖</button>
                        </div>
                    `;
                    fileList.append(itemHtml);
                    urlInput.val('');
                    $('#url-title-' + specId).val('');
                } else {
                    alert(res.message);
                }
            } catch(e) {
                console.error(e);
                alert('<?= htmlspecialchars(lang('js_err_download_url_fail'), ENT_QUOTES); ?>');
            }
        });
    }

    // --- Бесконечный скролл заказчиков в сайдбаре ---
    let custOffset = <?= isset($per_page) ? $per_page : 25; ?>;
    let custLimit = <?= isset($per_page) ? $per_page : 25; ?>;
    let custHasMore = true;
    let custIsLoading = false;
    const activeCustomerId = <?= isset($active_customer_id) ? $active_customer_id : 'null'; ?>;

    $('#customersSidebarList').on('scroll', function() {
        if (!custHasMore || custIsLoading) return;

        let scrollTop = $(this).scrollTop();
        let scrollHeight = $(this)[0].scrollHeight;
        let innerHeight = $(this).innerHeight();

        if (scrollTop + innerHeight >= scrollHeight - 50) {
            custIsLoading = true;
            
            $.post('<?= site_url("customers/load_more_ajax"); ?>', { offset: custOffset, active_customer_id: activeCustomerId }, function(response) {
                let res = JSON.parse(response);
                if (res.status === 'success') {
                    if (res.html && res.html.trim() !== '') {
                        $('#customersSidebarList').append(res.html);
                        custOffset += custLimit;
                    }
                    custHasMore = res.has_more;
                }
                custIsLoading = false;
            }).fail(function() {
                custIsLoading = false;
            });
        }
    });
</script>
