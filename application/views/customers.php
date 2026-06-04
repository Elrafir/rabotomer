<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-end mb-8">
        <h2 class="text-4xl font-black text-gray-800"><?= lang('customers_title'); ?></h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Форма добавления заказчика -->
        <div class="md:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-xl font-bold mb-4 text-gray-800"><?= lang('customers_new'); ?></h3>
                <?php echo form_open('customers/add', ['class' => 'space-y-4']); ?>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('customers_name'); ?></label>
                        <input type="text" name="name" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('customers_rate'); ?></label>
                        <input type="number" step="0.01" name="hourly_rate" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow transition-colors">
                        <?= lang('btn_save'); ?>
                    </button>
                <?php echo form_close(); ?>
            </div>
        </div>

        <!-- Таблица заказчиков -->
        <div class="md:col-span-2">
            <?php if (empty($customers)): ?>
                <div class="bg-white p-8 rounded-2xl border border-gray-100 text-center shadow-sm">
                    <p class="text-lg text-gray-500 font-medium">Нет добавленных заказчиков</p>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 border-b border-gray-100 text-gray-500 uppercase tracking-wider text-xs font-bold">
                            <tr>
                                <th class="px-6 py-3"><?= lang('customers_name'); ?></th>
                                <th class="px-6 py-3"><?= lang('customers_rate'); ?></th>
                                <th class="px-6 py-3 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php foreach ($customers as $c): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-3 font-semibold text-gray-800"><?= htmlspecialchars($c['name'] ?? ''); ?></td>
                                    <td class="px-6 py-3 font-mono text-blue-600 font-bold"><?= number_format($c['hourly_rate'], 2, '.', ' '); ?></td>
                                    <td class="px-6 py-3 text-right">
                                        <a href="<?= site_url('customers/delete/'.$c['id']); ?>" onclick="return confirm('Удалить заказчика?');" class="text-red-400 hover:text-red-600 transition-colors inline-block p-2" title="<?= lang('btn_delete'); ?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
