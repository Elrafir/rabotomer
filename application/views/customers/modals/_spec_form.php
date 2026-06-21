<!-- УНИВЕРСАЛЬНАЯ модалка для создания и редактирования ТЗ -->
<!-- Один div #specFormModal работает в двух режимах: add (новое ТЗ) и edit (редактирование) -->
<!-- Режим переключается через JS: CustomersModals.openSpecFormModal(mode, data) -->
<!-- data-action-add и data-action-edit хранят URL-ы экшенов для каждого режима -->
<!-- Переменные: $active_customer_id, $active_customer, $customer_tasks -->
<div id="specFormModal"
     class="hidden fixed inset-0 z-[99999] bg-black bg-opacity-50 flex items-center justify-center p-4 js-modal-overlay"
     data-modal="specFormModal"
     data-mode="add"
     data-action-add="<?= site_url('customers/add_spec') ?>"
     data-action-edit="<?= site_url('customers/edit_spec') ?>">

    <!-- Ограничиваем высоту модального окна в 85% высоты экрана -->
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl transform transition-all relative max-h-[85vh] flex flex-col overflow-hidden js-modal-content">

        <!-- Шапка модального окна (фиксированная сверху) -->
        <div class="p-6 border-b border-gray-100 flex justify-between items-center flex-shrink-0">
            <!-- Заголовок: текст меняется через JS в зависимости от режима -->
            <h3 class="text-2xl font-black text-gray-800">
                <span id="specFormTitle"><?= lang('cust_new_spec_title'); ?></span>
            </h3>
            <!-- Кнопка закрытия модалки -->
            <button type="button" class="js-modal-close text-gray-400 hover:text-gray-600 transition-colors" data-modal="specFormModal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Единая форма для добавления и редактирования ТЗ -->
        <!-- action переписывается через JS при открытии модалки -->
        <form id="specForm" method="post" action="<?= site_url('customers/add_spec') ?>" class="flex flex-col flex-grow overflow-hidden">
            <!-- Скрытое поле ID заказчика -->
            <input type="hidden" name="customer_id" value="<?= $active_customer_id ?>">
            <!-- Скрытое поле ID ТЗ (заполняется при режиме edit) -->
            <input type="hidden" name="spec_id" id="specFormSpecId" value="">

            <!-- Основная рабочая область формы — скроллируется при переполнении -->
            <div class="p-6 overflow-y-auto flex-grow">
                <!-- Двухколоночный макет на средних+ экранах -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Левая колонка: параметры и поля настроек ТЗ -->
                    <div class="space-y-4">
                        <!-- Название технического задания -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_spec_title_label'); ?></label>
                            <input type="text" name="title" id="specFormTitleInput"
                                   class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                   required
                                   placeholder="<?= htmlspecialchars(lang('cust_spec_title_placeholder'), ENT_QUOTES); ?>">
                        </div>

                        <!-- Путь к директории с рабочими файлами -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Путь к директории с рабочими файлами</label>
                            <input type="text" name="files_dir" id="specFormFilesDir"
                                   class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                   placeholder="/mnt/share/project_materials (абсолютный путь)">
                        </div>

                        <!-- Стоимость и предоплата ТЗ -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Цена -->
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_price_label'); ?></label>
                                <input type="number" name="price" id="specFormPrice" step="0.01" min="0"
                                       value="<?= !empty($active_customer) ? htmlspecialchars($active_customer['default_price']) : '0.00' ?>"
                                       class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            </div>
                            <!-- Предоплата -->
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_prepayment_label'); ?></label>
                                <input type="number" name="prepayment" id="specFormPrepayment" step="0.01" min="0"
                                       value="<?= !empty($active_customer) ? htmlspecialchars($active_customer['default_prepayment']) : '0.00' ?>"
                                       class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            </div>
                        </div>

                        <!-- Тип оплаты (почасовая/фикс) -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_payment_type_label'); ?></label>
                            <select name="payment_type" id="specFormPaymentType"
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <option value="hourly" <?= (!empty($active_customer) && $active_customer['default_payment_type'] == 'hourly') ? 'selected' : '' ?>><?= lang('finance_hourly'); ?></option>
                                <option value="fixed" <?= (!empty($active_customer) && $active_customer['default_payment_type'] == 'fixed') ? 'selected' : '' ?>><?= lang('finance_fixed'); ?></option>
                            </select>
                        </div>

                        <!-- Привязка ТЗ к задачам (чекбоксы с локальным скроллом) -->
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_link_tasks_label'); ?></label>
                            <div class="max-h-40 overflow-y-auto border border-gray-200 rounded-xl p-3 bg-gray-50 space-y-2">
                                <?php if (empty($customer_tasks)): ?>
                                    <!-- Заглушка при отсутствии задач -->
                                    <p class="text-xs text-gray-400 italic"><?= lang('cust_no_tasks_available'); ?></p>
                                <?php else: ?>
                                    <!-- Цикл по задачам заказчика -->
                                    <?php foreach ($customer_tasks as $task): ?>
                                        <label class="flex items-center gap-2 text-sm cursor-pointer p-1 hover:bg-gray-100 rounded">
                                            <!-- Чекбокс привязки задачи к ТЗ -->
                                            <input type="checkbox" name="linked_tasks[]" value="<?= $task['id'] ?>"
                                                   class="spec-task-checkbox rounded text-blue-600 focus:ring-blue-500">
                                            <span><?= htmlspecialchars($task['title']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Правая колонка: WYSIWYG редактор Quill для содержимого ТЗ -->
                    <div class="flex flex-col h-full min-h-[320px] md:min-h-0">
                        <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_spec_content_label'); ?></label>
                        <!-- Контейнер для Quill-редактора (или textarea-фоллбэка) -->
                        <div id="spec-editor-container" class="flex-grow min-h-[260px] md:h-auto bg-gray-50 border border-gray-200 rounded-xl overflow-hidden flex flex-col">
                            <!-- Единственный Quill-редактор для обоих режимов -->
                            <div id="spec-editor" class="flex-grow bg-white"></div>
                        </div>
                        <!-- Скрытое поле для передачи HTML-контента Quill при сабмите -->
                        <input type="hidden" name="content" id="specFormContent">
                    </div>

                </div>
            </div>

            <!-- Подвал модального окна (фиксированный снизу) -->
            <div class="p-6 border-t border-gray-100 flex justify-end gap-3 flex-shrink-0 bg-gray-50 rounded-b-3xl">
                <!-- Кнопка отмены -->
                <button type="button" class="js-modal-close px-6 py-3 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold rounded-xl transition-colors text-sm" data-modal="specFormModal">
                    <?= lang('btn_cancel'); ?>
                </button>
                <!-- Кнопка сохранения — сабмит формы (JS перехватывает для переноса контента Quill) -->
                <button type="submit" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg transition-colors text-sm">
                    <?= lang('btn_save'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
