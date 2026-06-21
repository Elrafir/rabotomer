<!-- Модальное окно просмотра информации о заказчике -->
<!-- Отображается только при наличии активного заказчика -->
<?php if (!empty($active_customer)): ?>
<div id="customerInfoModal" class="hidden fixed inset-0 z-[99999] bg-black bg-opacity-50 flex items-center justify-center p-4 js-modal-overlay" data-modal="customerInfoModal">

    <!-- Содержимое модалки -->
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 transform transition-all relative max-h-[90vh] overflow-y-auto js-modal-content">

        <!-- Кнопка закрытия модалки (крестик) -->
        <button type="button" class="js-modal-close absolute top-6 right-6 text-gray-400 hover:text-gray-600" data-modal="customerInfoModal">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>

        <!-- Заголовок модалки -->
        <h3 class="text-2xl font-bold mb-4 text-gray-800"><?= lang('cust_details_title'); ?></h3>

        <!-- Информация о заказчике -->
        <div class="border-t border-gray-100 pt-4 space-y-4">
            <!-- Имя заказчика -->
            <div>
                <span class="text-xs uppercase font-bold text-gray-400"><?= lang('cust_name_label'); ?></span>
                <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($active_customer['name']) ?></p>
            </div>
            <!-- Заметки -->
            <div>
                <span class="text-xs uppercase font-bold text-gray-400"><?= lang('cust_notes_label'); ?></span>
                <p class="text-sm text-gray-600 whitespace-pre-wrap leading-relaxed"><?= !empty($active_customer['notes']) ? htmlspecialchars($active_customer['notes']) : lang('cust_no_notes') ?></p>
            </div>
            <!-- Финансовые параметры по умолчанию -->
            <div class="grid grid-cols-2 gap-4 border-t border-gray-100 pt-4">
                <!-- Цена по умолчанию -->
                <div>
                    <span class="text-xs uppercase font-bold text-gray-400"><?= lang('cust_default_price_label'); ?></span>
                    <p class="text-sm font-bold text-gray-800"><?= number_format($active_customer['default_price'], 2, '.', ' ') ?> руб.</p>
                </div>
                <!-- Предоплата по умолчанию -->
                <div>
                    <span class="text-xs uppercase font-bold text-gray-400"><?= lang('cust_default_prepayment_label'); ?></span>
                    <p class="text-sm font-bold text-gray-800"><?= number_format($active_customer['default_prepayment'], 2, '.', ' ') ?> руб.</p>
                </div>
            </div>
            <!-- Тип оплаты по умолчанию -->
            <div>
                <span class="text-xs uppercase font-bold text-gray-400"><?= lang('cust_default_payment_type_label'); ?></span>
                <p class="text-sm font-bold text-gray-800"><?= format_payment_type($active_customer['default_payment_type']) ?></p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
