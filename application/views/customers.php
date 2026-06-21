<!--
  Раздел: Заказчики и Технические Задания (ТЗ).
  Облегчённый каркас — подключает паршалы и модули.
  Переменные: $customers, $active_customer_id, $active_customer,
              $customer_tasks_tree, $customer_tasks_has_more,
              $specs, $customer_tasks
-->

<!-- Корневой контейнер раздела с data-атрибутом для передачи activeCustomerId в JS -->
<div id="customers-root" class="w-full h-full flex flex-col gap-6"
     data-active-customer-id="<?= isset($active_customer_id) ? (int)$active_customer_id : '' ?>">

    <!-- Шапка раздела -->
    <div class="flex justify-between items-end mb-2 flex-shrink-0">
        <div class="flex items-center gap-6">
            <!-- Логотип раздела -->
            <img src="<?= base_url('assets/img/customers_logo.png') ?>" alt="Customers Logo" class="w-16 h-16 object-cover rounded-2xl shadow-sm">
            <!-- Заголовок раздела -->
            <h2 class="text-3xl font-black text-gray-800"><?= lang('cust_title_and_spec'); ?></h2>
        </div>
    </div>

    <!-- Двухколоночный макет: сайдбар + основной контент -->
    <div class="flex-grow flex flex-col md:flex-row gap-6 overflow-hidden min-h-[500px]">

        <!-- ЛЕВАЯ КОЛОНКА: Навигационный сайдбар со списком заказчиков -->
        <?php $this->load->view('customers/_sidebar', [
            'customers'          => $customers,
            'active_customer_id' => $active_customer_id
        ]); ?>

        <!-- ПРАВАЯ КОЛОНКА: Детальная информация по выбранному заказчику -->
        <div class="flex-grow w-full md:w-3/4 flex flex-col overflow-y-auto pb-32 pr-2">
            <?php if (empty($active_customer)): ?>
                <!-- Заглушка, если заказчик не выбран -->
                <div class="bg-white p-12 rounded-3xl border border-gray-100 text-center shadow-sm my-auto">
                    <span class="text-6xl mb-4 block text-gray-300">👥</span>
                    <h3 class="text-xl font-bold text-gray-700 mb-2"><?= lang('cust_no_customers_title'); ?></h3>
                    <p class="text-gray-500 mb-6"><?= lang('cust_no_customers_desc'); ?></p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <!-- Шапка активного заказчика с кнопками действий -->
                    <?php $this->load->view('customers/_customer_header', [
                        'active_customer' => $active_customer
                    ]); ?>

                    <!-- Блок дерева задач заказчика -->
                    <?php $this->load->view('customers/_task_tree_block', [
                        'customer_tasks_tree'     => $customer_tasks_tree,
                        'customer_tasks_has_more'  => $customer_tasks_has_more
                    ]); ?>

                    <!-- Раздел технических заданий (ТЗ) -->
                    <?php $this->load->view('customers/_specs_block', [
                        'specs'          => $specs,
                        'customer_tasks' => $customer_tasks
                    ]); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== МОДАЛЬНЫЕ ОКНА ===== -->

<!-- Модалка добавления заказчика -->
<?php $this->load->view('customers/modals/_add_customer'); ?>

<!-- Модалка редактирования заказчика -->
<?php $this->load->view('customers/modals/_edit_customer'); ?>

<!-- Модалка просмотра информации о заказчике -->
<?php $this->load->view('customers/modals/_customer_info', [
    'active_customer' => $active_customer ?? null
]); ?>

<!-- Универсальная модалка создания/редактирования ТЗ -->
<?php $this->load->view('customers/modals/_spec_form', [
    'active_customer_id' => $active_customer_id,
    'active_customer'    => $active_customer ?? null,
    'customer_tasks'     => $customer_tasks ?? []
]); ?>

<!-- Полноэкранный просмотрщик документов -->
<?php $this->load->view('customers/modals/_doc_viewer'); ?>

<!-- ===== ПОДКЛЮЧЕНИЕ РЕСУРСОВ ===== -->

<!-- Quill WYSIWYG редактор (локальная копия вместо CDN) -->
<link href="<?= base_url('assets/vendor/quill/quill.snow.css') ?>" rel="stylesheet">
<script src="<?= base_url('assets/vendor/quill/quill.js') ?>"></script>
<script src="<?= base_url('assets/vendor/quill/image-resize.min.js') ?>"></script>

<!-- JS-модули раздела «Заказчики» -->
<script src="<?= base_url('assets/js/customers/utils.js?v=' . time()) ?>"></script>
<script src="<?= base_url('assets/js/customers/modals.js?v=' . time()) ?>"></script>
<script src="<?= base_url('assets/js/customers/spec-editor.js?v=' . time()) ?>"></script>
<script src="<?= base_url('assets/js/customers/file-upload.js?v=' . time()) ?>"></script>
<script src="<?= base_url('assets/js/customers/file-preview.js?v=' . time()) ?>"></script>
<script src="<?= base_url('assets/js/customers/infinite-scroll.js?v=' . time()) ?>"></script>
<script src="<?= base_url('assets/js/customers/init.js?v=' . time()) ?>"></script>
