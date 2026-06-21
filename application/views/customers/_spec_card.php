<!-- Одна карточка ТЗ (аккордеон) -->
<!-- Переменные: $spec (данные одного ТЗ), $customer_tasks (плоский список задач для связей) -->
<div class="border border-gray-100 rounded-2xl p-5 hover:shadow-md transition-shadow relative spec-card">

    <!-- Шапка карточки ТЗ (кликабельная для раскрытия/скрытия) -->
    <div class="flex justify-between items-start mb-3 border-b border-gray-50 pb-3 cursor-pointer select-none toggle-spec">
        <!-- Левая часть: название и бейджи с метаданными -->
        <div>
            <!-- Название ТЗ с иконкой раскрытия -->
            <h5 class="text-base font-black text-gray-800 flex items-center gap-2">
                <span><?= htmlspecialchars($spec['title']) ?></span>
                <!-- Иконка-стрелка для аккордеона -->
                <svg class="w-4 h-4 transition-transform duration-200 icon-expand text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                </svg>
            </h5>
            <!-- Мета-информация: дата, цена, предоплата, тип оплаты -->
            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-xs text-gray-500">
                <!-- Дата создания -->
                <span><?= lang('cust_created_at'); ?> <?= date('d.m.Y H:i', strtotime($spec['created_at'])) ?></span>
                <!-- Стоимость -->
                <span>💰 <?= lang('cust_price_badge'); ?> <strong><?= number_format($spec['price'], 2, '.', ' ') ?> руб.</strong></span>
                <!-- Предоплата -->
                <span>💳 <?= lang('cust_prepayment_badge'); ?> <strong><?= number_format($spec['prepayment'], 2, '.', ' ') ?> руб.</strong></span>
                <!-- Тип оплаты -->
                <span>🛠️ <?= lang('cust_payment_badge'); ?> <strong><?= format_payment_type($spec['payment_type']) ?></strong></span>
            </div>
        </div>

        <!-- Правая часть: кнопки управления ТЗ -->
        <div class="flex gap-2">
            <!-- Кнопка редактирования ТЗ с data-атрибутами для JS -->
            <button class="edit-spec-btn text-gray-400 hover:text-blue-600 transition-colors p-1"
                    data-id="<?= $spec['id'] ?>"
                    data-title="<?= htmlspecialchars($spec['title'], ENT_QUOTES, 'UTF-8') ?>"
                    data-price="<?= $spec['price'] ?>"
                    data-prepayment="<?= $spec['prepayment'] ?>"
                    data-payment-type="<?= $spec['payment_type'] ?>"
                    data-files-dir="<?= htmlspecialchars($spec['files_dir'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-linked-tasks="<?= htmlspecialchars(json_encode($spec['linked_task_ids'] ?? [])) ?>"
                    title="<?= htmlspecialchars(lang('btn_edit'), ENT_QUOTES); ?>">
                ✏️
            </button>

            <!-- Скрытый контейнер с HTML-контентом ТЗ для JS -->
            <div id="spec-content-data-<?= $spec['id'] ?>" class="hidden"><?= htmlspecialchars($spec['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>

            <!-- Ссылка удаления ТЗ с подтверждением через JS-делегирование -->
            <a href="<?= site_url('customers/delete_spec/'.$spec['id']) ?>"
               class="text-gray-400 hover:text-red-600 transition-colors p-1 js-confirm-delete"
               data-confirm-message="<?= htmlspecialchars(lang('cust_delete_spec_confirm'), ENT_QUOTES); ?>"
               title="<?= htmlspecialchars(lang('btn_delete'), ENT_QUOTES); ?>">
                🗑️
            </a>
        </div>
    </div>

    <!-- Сворачиваемая часть ТЗ (скрыта по умолчанию) -->
    <div class="spec-body" style="display: none;">
        <!-- Текст ТЗ (рендерим HTML из Quill-редактора) -->
        <div class="text-gray-700 text-sm leading-relaxed mb-4 prose max-w-none">
            <?= $spec['content'] ?>
        </div>

        <!-- Привязанные задачи -->
        <div class="mb-4">
            <!-- Заголовок секции привязанных задач -->
            <span class="text-xs font-bold text-gray-400 uppercase"><?= lang('cust_spec_linked_tasks'); ?></span>
            <div class="flex flex-wrap gap-2 mt-1">
                <?php
                // Флаг наличия привязанных задач
                $linked_tasks_found = false;
                // Перебираем все задачи заказчика и проверяем привязку к текущему ТЗ
                foreach ($customer_tasks as $task) {
                    if (in_array($task['id'], $spec['linked_task_ids'] ?? [])) {
                        $linked_tasks_found = true;
                        // Цвет задачи для цветной метки
                        $color = !empty($task['color']) ? $task['color'] : '#e5e7eb';
                        echo '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border" style="border-left-color: ' . $color . '; border-left-width: 4px;">' . htmlspecialchars($task['title']) . '</span>';
                    }
                }
                // Если привязанных задач нет — показываем заглушку
                if (!$linked_tasks_found) {
                    echo '<span class="text-xs text-gray-400 italic">' . lang('cust_spec_no_linked_tasks') . '</span>';
                }
                ?>
            </div>
        </div>

        <!-- Подключаем паршал блока файлов ТЗ -->
        <?php $this->load->view('customers/_spec_files', ['spec' => $spec]); ?>
    </div>
</div>
