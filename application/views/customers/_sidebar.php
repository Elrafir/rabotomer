<!-- Левая колонка: Навигационный сайдбар со списком заказчиков -->
<!-- Содержит кнопку добавления нового заказчика и список существующих -->
<!-- Переменные: $customers (массив заказчиков), $active_customer_id (ID выбранного) -->
<div class="w-full md:w-1/4 flex flex-col bg-white rounded-3xl shadow-sm border border-gray-100 p-4 overflow-hidden flex-shrink-0">

    <!-- Кнопка добавления нового заказчика -->
    <div class="mb-4">
        <!-- data-action указывает JS-обработчику, что нужно открыть модалку добавления -->
        <button data-action="open-add-customer" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-colors flex items-center justify-center gap-2 text-sm">
            <!-- Иконка плюса -->
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <!-- Текст кнопки из языкового файла -->
            <?= lang('customers_new'); ?>
        </button>
    </div>

    <!-- Заголовок списка заказчиков -->
    <h3 class="text-xs uppercase tracking-wider font-bold mb-2 text-gray-400 pl-2"><?= lang('cust_list_title'); ?></h3>

    <!-- Прокручиваемый список заказчиков с поддержкой бесконечного скролла -->
    <div id="customersSidebarList" class="flex-grow overflow-y-auto space-y-1 pr-1">
        <?php if (empty($customers)): ?>
            <!-- Заглушка при отсутствии заказчиков -->
            <div class="text-center text-gray-400 py-8 text-xs italic"><?= lang('cust_empty_list'); ?></div>
        <?php else: ?>
            <!-- Цикл по всем заказчикам -->
            <?php foreach ($customers as $c): ?>
                <!-- Ссылка-элемент списка, активный заказчик выделяется стилем -->
                <a href="<?= site_url('customers/index/'.$c['id']) ?>" class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl transition-colors text-sm <?= $c['id'] == $active_customer_id ? 'bg-blue-50 text-blue-600 font-bold border-l-4 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <!-- Иконка пользователя -->
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <!-- Имя заказчика с обрезкой длинного текста -->
                    <span class="truncate"><?= htmlspecialchars($c['name']) ?></span>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
