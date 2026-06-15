<div class="w-full h-full flex flex-col gap-6 max-h-[calc(100vh-120px)]">
    <!-- Шапка пакета -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex-shrink-0">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="<?= site_url('calculations') ?>" class="text-gray-400 hover:text-blue-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="text-2xl font-black text-gray-800"><?= htmlspecialchars($package['title']) ?></h2>
                <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-bold uppercase tracking-wide">
                    <?php 
                        $status_map = ['draft' => 'Черновик', 'active' => 'В работе', 'completed' => 'Завершен', 'paid' => 'Оплачен', 'archived' => 'В архиве'];
                        echo $status_map[$package['status']] ?? 'Неизвестно';
                    ?>
                </span>
            </div>
            <?php if (!empty($package['notes'])): ?>
                <p class="text-gray-600 text-sm ml-9"><?= nl2br(htmlspecialchars($package['notes'])) ?></p>
            <?php endif; ?>
        </div>
        <div class="flex gap-3">
            <button onclick="$('#editPackageModal').removeClass('hidden')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-4 rounded-xl shadow-sm transition-colors text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Изменить
            </button>
        </div>
    </div>

    <!-- Дашборд статистики -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 flex-shrink-0">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="text-xs text-gray-500 uppercase font-bold mb-1">Общая сумма</div>
            <div class="text-2xl font-mono font-black text-emerald-600"><?= number_format($package['total_sum'], 2, '.', ' ') ?></div>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="text-xs text-gray-500 uppercase font-bold mb-1">Календарное время</div>
            <div class="text-2xl font-mono font-black text-gray-800"><?= number_format($package['calendar_time'], 2, '.', '') ?> <span class="text-sm font-normal text-gray-500"><?= htmlspecialchars($package['calendar_time_type']) ?></span></div>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="text-xs text-gray-500 uppercase font-bold mb-1">Время по трекеру (факт)</div>
            <div class="text-2xl font-mono font-black text-blue-600">
                <?php
                    $hours = floor($stats['total_time_seconds'] / 3600);
                    $mins = floor(($stats['total_time_seconds'] % 3600) / 60);
                    echo sprintf("%02d:%02d", $hours, $mins);
                ?>
                <span class="text-sm font-normal text-gray-500">чч:мм</span>
            </div>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-blue-100 bg-blue-50">
            <div class="text-xs text-blue-500 uppercase font-bold mb-1">Стоимость 1 часа (факт)</div>
            <div class="text-2xl font-mono font-black text-blue-700">
                <?php
                    $total_hours = $stats['total_time_seconds'] / 3600;
                    if ($total_hours > 0 && $package['total_sum'] > 0) {
                        echo number_format($package['total_sum'] / $total_hours, 2, '.', ' ');
                    } else {
                        echo "0.00";
                    }
                ?>
            </div>
        </div>
    </div>

    <!-- Интерфейс подбора задач -->
    <div class="flex-grow flex flex-col md:flex-row gap-6 overflow-hidden min-h-[400px]">
        <!-- Левая колонка: Поиск и доступные задачи -->
        <div class="w-full md:w-1/2 flex flex-col bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50 flex-shrink-0">
                <h3 class="font-bold text-gray-700 mb-3">Поиск задач</h3>
                <div class="flex gap-2">
                    <input type="date" id="searchDateStart" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <input type="date" id="searchDateEnd" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <button onclick="searchTasks()" class="bg-blue-600 text-white p-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-2">Поиск задач, над которыми велась работа в этот период</p>
            </div>
            
            <div id="availableTasksContainer" class="flex-grow p-4 overflow-y-auto bg-gray-50/50" ondrop="dropToAvailable(event)" ondragover="allowDrop(event)">
                <div class="text-center text-gray-400 py-8 text-sm italic">Выберите период и нажмите поиск</div>
            </div>
        </div>

        <!-- Правая колонка: Задачи в пакете -->
        <div class="w-full md:w-1/2 flex flex-col bg-white rounded-3xl shadow-sm border border-emerald-100 overflow-hidden">
            <div class="p-4 border-b border-emerald-100 bg-emerald-50 flex-shrink-0 flex justify-between items-center">
                <h3 class="font-bold text-emerald-800">Задачи в пакете (<?= count($stats['tasks_data']) ?>)</h3>
            </div>
            
            <div id="packageTasksContainer" class="flex-grow p-4 overflow-y-auto bg-emerald-50/30" ondrop="dropToPackage(event)" ondragover="allowDrop(event)">
                <?php if (empty($stats['tasks_data'])): ?>
                    <div class="text-center text-gray-400 py-8 text-sm italic" id="emptyPackageHint">Перетащите задачи сюда</div>
                <?php else: ?>
                    <?php foreach ($stats['tasks_data'] as $t): ?>
                        <div class="task-draggable bg-white border border-emerald-200 p-3 mb-2 rounded shadow-sm cursor-move flex justify-between items-center" data-id="<?= $t['id'] ?>" draggable="true" ondragstart="drag(event)">
                            <div>
                                <span class="font-bold text-sm text-gray-800"><?= htmlspecialchars($t['title']) ?></span><br>
                                <span class="text-xs text-gray-500"><?= htmlspecialchars($t['customer_name'] ?? 'Без заказчика') ?></span>
                                <span class="text-xs text-blue-500 ml-2 font-mono">
                                    ⏱ <?php 
                                        $h = floor($t['tracked_seconds'] / 3600);
                                        $m = floor(($t['tracked_seconds'] % 3600) / 60);
                                        echo sprintf("%02d:%02d", $h, $m);
                                    ?>
                                </span>
                            </div>
                            <button type="button" class="text-red-400 hover:text-red-600 p-1" onclick="removeTaskFromPackage(<?= $t['id'] ?>)">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="currentPackageId" value="<?= $package['id'] ?>">

<!-- Модальное окно редактирования -->
<div id="editPackageModal" onclick="$('#editPackageModal').addClass('hidden')" class="hidden fixed inset-0 z-[120] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 transform transition-all relative max-h-[90vh] flex flex-col overflow-y-auto">
        <button type="button" onclick="$('#editPackageModal').addClass('hidden')" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-2xl font-bold mb-6 text-gray-800">Редактировать пакет</h3>
        
        <?php echo form_open('calculations/update/'.$package['id'], ['class' => 'space-y-4']); ?>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Название пакета</label>
                <input type="text" name="title" value="<?= htmlspecialchars($package['title']) ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>
            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Статус</label>
                    <select name="status" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <option value="draft" <?= $package['status'] == 'draft' ? 'selected' : '' ?>>Черновик</option>
                        <option value="active" <?= $package['status'] == 'active' ? 'selected' : '' ?>>В работе</option>
                        <option value="completed" <?= $package['status'] == 'completed' ? 'selected' : '' ?>>Завершен</option>
                        <option value="paid" <?= $package['status'] == 'paid' ? 'selected' : '' ?>>Оплачен</option>
                        <option value="archived" <?= $package['status'] == 'archived' ? 'selected' : '' ?>>В архиве</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Сумма</label>
                    <input type="number" step="0.01" name="total_sum" value="<?= $package['total_sum'] ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="flex-1">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Затрачено</label>
                    <div class="flex gap-2">
                        <input type="number" step="0.01" name="calendar_time" value="<?= $package['calendar_time'] ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" min="0">
                        <select name="calendar_time_type" class="w-full px-2 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <option value="days" <?= $package['calendar_time_type'] == 'days' ? 'selected' : '' ?>>Дни</option>
                            <option value="hours" <?= $package['calendar_time_type'] == 'hours' ? 'selected' : '' ?>>Часы</option>
                            <option value="weeks" <?= $package['calendar_time_type'] == 'weeks' ? 'selected' : '' ?>>Недели</option>
                        </select>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Заметки / Комментарии</label>
                <textarea name="notes" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"><?= htmlspecialchars($package['notes']) ?></textarea>
            </div>
            
            <button type="submit" class="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-colors">
                Сохранить
            </button>
        <?php echo form_close(); ?>
    </div>
</div>

<script src="<?= base_url('assets/js/calculations.js?v=' . time()) ?>"></script>
