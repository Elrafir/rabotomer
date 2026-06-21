<!-- Модальное окно добавления заказчика -->
<!-- Модалка появляется по нажатию кнопки data-action="open-add-customer" -->
<div id="addCustomerModal" class="hidden fixed inset-0 z-[99999] bg-black bg-opacity-50 flex items-center justify-center p-4 js-modal-overlay" data-modal="addCustomerModal">

    <!-- Содержимое модалки (клик по оверлею закрывает, но не по контенту) -->
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 transform transition-all relative max-h-[90vh] overflow-y-auto js-modal-content">

        <!-- Кнопка закрытия модалки (крестик) -->
        <button type="button" class="js-modal-close absolute top-6 right-6 text-gray-400 hover:text-gray-600" data-modal="addCustomerModal">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>

        <!-- Заголовок модалки -->
        <h3 class="text-2xl font-bold mb-6 text-gray-800"><?= lang('customers_new'); ?></h3>

        <!-- Форма добавления заказчика -->
        <?php echo form_open('customers/add', ['class' => 'space-y-4']); ?>
            <!-- Поле имени заказчика -->
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_name_label'); ?></label>
                <input type="text" name="name" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>
            <!-- Поле заметок -->
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_notes_label'); ?></label>
                <textarea name="notes" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="<?= htmlspecialchars(lang('cust_notes_placeholder'), ENT_QUOTES); ?>"></textarea>
            </div>
            <!-- Сетка: цена и предоплата по умолчанию -->
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
            <!-- Выбор типа оплаты -->
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('cust_default_payment_type_label'); ?></label>
                <select name="default_payment_type" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="hourly"><?= lang('finance_hourly'); ?></option>
                    <option value="fixed"><?= lang('finance_fixed'); ?></option>
                </select>
            </div>
            <!-- Кнопка отправки формы -->
            <button type="submit" class="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-colors">
                <?= lang('btn_create'); ?>
            </button>
        <?php echo form_close(); ?>
    </div>
</div>
