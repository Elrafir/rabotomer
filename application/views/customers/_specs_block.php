<!-- Раздел технических заданий (ТЗ) заказчика -->
<!-- Переменные: $specs (массив ТЗ), $customer_tasks (плоский список задач для отображения связей) -->
<div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">

    <!-- Заголовок блока и кнопка добавления нового ТЗ -->
    <div class="flex justify-between items-center mb-6">
        <!-- Заголовок с иконкой -->
        <h4 class="text-lg font-black text-gray-800 flex items-center gap-2">
            📝 <?= lang('cust_specs_title'); ?>
        </h4>
        <!-- Кнопка добавления ТЗ (обрабатывается через делегированный JS) -->
        <button data-action="open-add-spec" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-xl shadow transition-colors text-xs flex items-center gap-1">
            ➕ <?= lang('cust_create_spec_btn'); ?>
        </button>
    </div>

    <?php if (empty($specs)): ?>
        <!-- Заглушка при отсутствии ТЗ -->
        <div class="text-center py-12 text-gray-400 italic text-sm"><?= lang('cust_no_specs'); ?></div>
    <?php else: ?>
        <!-- Контейнер карточек ТЗ -->
        <div class="space-y-6">
            <?php foreach ($specs as $spec): ?>
                <!-- Подключаем паршал отдельной карточки ТЗ -->
                <?php $this->load->view('customers/_spec_card', ['spec' => $spec, 'customer_tasks' => $customer_tasks]); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
