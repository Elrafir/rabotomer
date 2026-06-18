<div class="w-full">
    <div class="flex justify-between items-end mb-6">
        <div class="flex items-center gap-6">
            <h2 class="text-3xl font-black text-gray-800">Калькуляция</h2>
        </div>
        <button onclick="$('#addPackageModal').removeClass('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Создать пакет
        </button>
    </div>

    <?php if (empty($packages)): ?>
        <div class="bg-white p-10 rounded-3xl shadow-sm text-center border border-gray-100">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            <h3 class="text-xl font-bold text-gray-700 mb-2">У вас еще нет пакетов калькуляции</h3>
            <p class="text-gray-500 mb-6">Сгруппируйте выполненные задачи, укажите общую сумму и рассчитайте свою эффективность.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($packages as $pkg): ?>
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow relative group">
                    <a href="<?= site_url('calculations/view/'.$pkg['id']) ?>" class="absolute inset-0 z-10"></a>
                    <div class="flex justify-between items-start mb-4 relative z-20">
                        <h3 class="text-xl font-bold text-gray-800 break-words pr-8"><?= htmlspecialchars($pkg['title']) ?></h3>
                        <a href="<?= site_url('calculations/delete/'.$pkg['id']) ?>" onclick="return confirm('Удалить пакет?');" class="text-gray-400 hover:text-red-500 transition-colors p-1" title="Удалить">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </a>
                    </div>
                    
                    <?php if (!empty($pkg['notes'])): ?>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?= htmlspecialchars($pkg['notes']) ?></p>
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-2 gap-4 mt-4 bg-gray-50 p-4 rounded-2xl">
                        <div>
                            <div class="text-xs text-gray-500 uppercase font-bold">Сумма</div>
                            <div class="font-mono text-lg font-bold text-blue-600"><?= number_format($pkg['total_sum'], 2, '.', ' ') ?></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 uppercase font-bold">Время (<?= htmlspecialchars($pkg['calendar_time_type']) ?>)</div>
                            <div class="font-mono text-lg font-bold text-gray-800"><?= number_format($pkg['calendar_time'], 2, '.', '') ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Модальное окно добавления пакета с z-index фиксом -->
<div id="addPackageModal" onclick="$('#addPackageModal').addClass('hidden')" class="hidden fixed inset-0 z-[99999] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 transform transition-all relative max-h-[90vh] flex flex-col overflow-y-auto">
        <button type="button" onclick="$('#addPackageModal').addClass('hidden')" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-2xl font-bold mb-6 text-gray-800">Новый пакет</h3>
        
        <?php echo form_open('calculations/create', ['class' => 'space-y-4']); ?>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Название пакета</label>
                <input type="text" name="title" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>
            
            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Статус</label>
                    <select name="status" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="draft">Черновик</option>
                        <option value="active">В работе</option>
                        <option value="completed">Завершен</option>
                        <option value="paid">Оплачен</option>
                        <option value="archived">В архиве</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Сумма (Опц.)</label>
                    <input type="number" step="0.01" name="total_sum" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="flex-1">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Затрачено</label>
                    <div class="flex gap-2">
                        <input type="number" step="0.01" name="calendar_time" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" min="0">
                        <select name="calendar_time_type" class="w-full px-2 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="days">Дни</option>
                            <option value="hours">Часы</option>
                            <option value="weeks">Недели</option>
                        </select>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Заметки / Комментарии</label>
                <textarea name="notes" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"></textarea>
            </div>
            
            <button type="submit" class="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-colors">
                Создать
            </button>
        <?php echo form_close(); ?>
    </div>
</div>
