<?php
/**
 * Шаблон рекурсивного вывода дерева задач заказчика
 * @var array $tasks - Список задач текущего уровня
 * @var int $level - Текущий уровень вложенности
 */
if (!empty($tasks)): ?>
    <ul class="space-y-2 <?= $level > 1 ? 'ml-4 pl-4 border-l-2 border-gray-200 mt-2 task-children' : 'task-tree-root' ?>" <?= $level > 1 ? 'style="display: none;"' : '' ?>>
        <?php foreach ($tasks as $task): 
            $has_children = !empty($task['children']);
            $color_dot = !empty($task['color']) ? "background-color: {$task['color']};" : "background-color: #e5e7eb;";
            $is_completed = isset($task['status']) && $task['status'] === 'completed';
        ?>
            <li class="p-3 bg-gray-50 border border-gray-100 rounded-xl flex flex-col gap-1 <?= $is_completed ? 'opacity-60' : '' ?>" data-task-id="<?= $task['id'] ?>">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <?php if ($has_children): ?>
                            <button class="toggle-children p-1 hover:bg-gray-200 rounded text-gray-500 transition-all flex items-center justify-center" type="button">
                                <span class="icon-expand inline-block transform transition-transform text-[10px]">▶</span>
                            </button>
                        <?php endif; ?>
                        <div class="w-3 h-3 rounded-full border border-gray-200 shadow-sm" style="<?= $color_dot ?>"></div>
                        <span class="font-semibold text-gray-800 text-sm <?= $is_completed ? 'line-through text-gray-400' : '' ?>">
                            <?= htmlspecialchars($task['title']) ?>
                        </span>
                        <?php if ($is_completed): ?>
                            <span class="text-[9px] font-bold text-gray-400 bg-gray-200/50 px-1.5 py-0.5 rounded-full uppercase border">Закрыт</span>
                        <?php endif; ?>
                    </div>
                    <span class="text-xs font-bold text-gray-500 font-mono bg-white px-2 py-0.5 rounded-full border"><?= $task['formatted_time'] ?></span>
                </div>
                <?php if ($has_children) {
                    $this->load->view('templates/customer_task_tree_loop', ['tasks' => $task['children'], 'level' => $level + 1]);
                } ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
