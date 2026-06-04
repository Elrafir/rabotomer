<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-end mb-8">
        <h2 class="text-4xl font-black text-gray-800"><?= lang('history_title'); ?></h2>
    </div>

    <?php if (empty($sessions)): ?>
        <div class="bg-white p-12 rounded-3xl border border-gray-100 text-center shadow-sm">
            <span class="text-6xl mb-4 block text-gray-300">📖</span>
            <p class="text-2xl text-gray-400 font-medium"><?= lang('history_empty'); ?></p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead class="bg-gray-50 border-b border-gray-100 text-gray-500 uppercase tracking-wider text-sm font-bold">
                        <tr>
                            <th class="px-6 py-4 w-1/5"><?= lang('history_col_date'); ?></th>
                            <th class="px-6 py-4 w-1/4"><?= lang('history_col_task'); ?></th>
                            <th class="px-6 py-4 w-1/6"><?= lang('history_col_duration'); ?></th>
                            <th class="px-6 py-4 w-auto"><?= lang('history_col_note'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($sessions as $s): ?>
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="px-6 py-5">
                                    <div class="flex flex-col">
                                        <span class="text-gray-900 font-bold text-lg"><?= $s['start_formatted']; ?></span>
                                        <span class="text-gray-400 text-sm flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                            <?= $s['end_formatted']; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <?php $color_bg = !empty($s['color']) ? "background-color: {$s['color']};" : "background-color: #e5e7eb;"; ?>
                                        <div class="w-4 h-4 rounded-full flex-shrink-0 shadow-sm" style="<?= $color_bg ?>"></div>
                                        <span class="text-gray-800 font-semibold text-lg"><?= htmlspecialchars($s['task_title'] ?? ''); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold font-mono bg-blue-50 text-blue-600 border border-blue-100">
                                        <?= $s['duration_formatted']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <?php if (!empty($s['note_safe'])): ?>
                                        <div class="text-gray-600 italic bg-gray-50 border-l-4 border-gray-200 px-4 py-2 rounded-r-lg group-hover:bg-white transition-colors">
                                            <?= $s['note_safe']; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-300">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
