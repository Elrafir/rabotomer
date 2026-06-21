<!-- Шапка активного заказчика с кнопками действий -->
<!-- Переменные: $active_customer (массив данных текущего заказчика) -->
<div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">

    <!-- Левая часть: имя заказчика и кнопка информации -->
    <div>
        <!-- Имя активного заказчика -->
        <h3 class="text-2xl font-black text-gray-800 mb-1"><?= htmlspecialchars($active_customer['name']) ?></h3>
        <!-- Кнопка открытия модалки информации (обрабатывается через делегированный JS) -->
        <button data-action="open-customer-info" class="text-blue-600 hover:underline text-sm font-semibold flex items-center gap-1">
            <!-- Иконка информации -->
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <!-- Текст кнопки из языкового файла -->
            <?= lang('cust_info_btn'); ?>
        </button>
    </div>

    <!-- Правая часть: кнопки редактирования и удаления -->
    <div class="flex gap-2">
        <!-- Кнопка редактирования заказчика с data-атрибутами для JS -->
        <button id="editCustomerBtn" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-xl shadow-sm transition-colors text-sm flex items-center gap-2"
                data-id="<?= $active_customer['id']; ?>"
                data-name="<?= htmlspecialchars($active_customer['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                data-price="<?= $active_customer['default_price'] ?>"
                data-prepayment="<?= $active_customer['default_prepayment'] ?>"
                data-payment-type="<?= $active_customer['default_payment_type'] ?>">
            ✏️ <?= lang('btn_edit'); ?>
        </button>

        <!-- Скрытый div с заметками — используется JS для предзаполнения модалки редактирования -->
        <div id="customer-notes-data-<?= $active_customer['id'] ?>" class="hidden"><?= htmlspecialchars($active_customer['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>

        <!-- Ссылка удаления заказчика с подтверждением (обрабатывается через JS-делегирование) -->
        <a href="<?= site_url('customers/delete/'.$active_customer['id']); ?>"
           class="bg-red-50 hover:bg-red-100 text-red-600 font-bold py-2.5 px-4 rounded-xl shadow-sm transition-colors text-sm flex items-center gap-2 js-confirm-delete"
           data-confirm-message="<?= htmlspecialchars(lang('cust_delete_confirm'), ENT_QUOTES); ?>">
            🗑️ <?= lang('btn_delete'); ?>
        </a>
    </div>
</div>
