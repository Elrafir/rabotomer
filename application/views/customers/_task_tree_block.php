<!-- Блок дерева задач текущего заказчика -->
<!-- Переменные: $customer_tasks_tree (иерархия задач), $customer_tasks_has_more (флаг пагинации) -->
<div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">

    <!-- Заголовок блока и чекбокс показа закрытых задач -->
    <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
        <!-- Заголовок с иконкой -->
        <h4 class="text-lg font-black text-gray-800 flex items-center gap-2">
            🗂️ <?= lang('cust_linked_tasks'); ?>
        </h4>
        <!-- Переключатель видимости закрытых задач -->
        <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-500 cursor-pointer select-none">
            <!-- Чекбокс обрабатывается в infinite-scroll.js -->
            <input type="checkbox" id="showClosedTasksToggle" class="rounded text-blue-600 focus:ring-blue-500">
            <span>Показывать закрытые заказы</span>
        </label>
    </div>

    <!-- Контейнер дерева задач с ограничением высоты и скроллом -->
    <!-- data-has-more используется JS для бесконечной подгрузки -->
    <div id="customerTasksContainer" class="max-h-96 overflow-y-auto pr-2 space-y-2" data-has-more="<?= !empty($customer_tasks_has_more) ? '1' : '0' ?>">
        <?php if (empty($customer_tasks_tree)): ?>
            <!-- Заглушка при отсутствии задач -->
            <p class="text-sm text-gray-400 italic empty-tasks-label"><?= lang('cust_no_tasks'); ?></p>
        <?php else: ?>
            <!-- Рекурсивный вывод дерева задач через шаблон -->
            <?php $this->load->view('templates/customer_task_tree_loop', ['tasks' => $customer_tasks_tree, 'level' => 1]); ?>
        <?php endif; ?>
    </div>
</div>
