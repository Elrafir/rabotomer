<!-- application/views/admin/users.php -->
<div class="max-w-6xl mx-auto min-h-[80vh] pb-32">
    
    <!-- Заголовок страницы и кнопка добавления -->
    <div class="flex justify-between items-center mb-10">
        <h1 class="text-4xl font-black text-gray-800"><?= lang('admin_title'); ?></h1>
        <button onclick="openAddUserModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded-xl text-xl shadow-lg transition-colors">
            <?= lang('admin_btn_add_user'); ?>
        </button>
    </div>

    <!-- Таблица пользователей -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 text-lg">
                    <th class="py-5 px-8 font-bold">ID</th>
                    <th class="py-5 px-8 font-bold"><?= lang('admin_col_login'); ?></th>
                    <th class="py-5 px-8 font-bold"><?= lang('admin_col_role'); ?></th>
                    <th class="py-5 px-8 font-bold text-center" title="<?= lang('admin_col_total_tasks'); ?>">Задачи</th>
                    <th class="py-5 px-8 font-bold text-center" title="<?= lang('admin_col_total_time'); ?>">Время</th>
                    <th class="py-5 px-8 font-bold text-right" title="<?= lang('admin_col_last_activity'); ?>">Активность</th>
                    <th class="py-5 px-8 font-bold text-right">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-5 px-8 text-xl text-gray-500 font-mono"><?= $u['id']; ?></td>
                        <td class="py-5 px-8 text-2xl font-bold text-gray-800"><?= htmlspecialchars($u['username'] ?? ''); ?></td>
                        <td class="py-5 px-8 text-xl">
                            <?php 
                            $roleName = htmlspecialchars(!empty($u['group_description']) ? $u['group_description'] : ($u['group_name'] ?? 'User'));
                            if ($u['group_id'] == 1 || $u['username'] === 'root'): ?>
                                <span class="bg-purple-100 text-purple-700 px-4 py-1 rounded-full font-bold text-sm uppercase tracking-wide"><?= $roleName ?></span>
                            <?php else: ?>
                                <span class="bg-blue-100 text-blue-700 px-4 py-1 rounded-full font-bold text-sm uppercase tracking-wide"><?= $roleName ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="py-5 px-8 text-xl text-center font-bold text-gray-600">
                            <?= (int)$u['total_tasks']; ?>
                        </td>
                        <td class="py-5 px-8 text-xl text-center font-bold text-blue-600">
                            <?php 
                                $sec = (int)$u['total_time_seconds'];
                                $h = floor($sec / 3600);
                                $m = floor(($sec % 3600) / 60);
                                echo sprintf(lang('time_format_hours_mins'), $h, $m);
                            ?>
                        </td>
                        <td class="py-5 px-8 text-lg text-right text-gray-400">
                            <?= $u['last_activity'] ? date('d.m.Y H:i', strtotime($u['last_activity'])) : '—'; ?>
                        </td>
                        <td class="py-5 px-8 text-right space-x-2">
                            <button onclick="openChangePasswordModal(<?= $u['id']; ?>)" class="bg-blue-50 hover:bg-blue-100 text-blue-500 hover:text-blue-700 font-bold py-2 px-4 rounded-xl transition-colors" title="<?= lang('admin_btn_change_password'); ?>">
                                🔑
                            </button>
                            <button onclick="openEditUserModal(<?= htmlspecialchars(json_encode([
                                'id' => $u['id'],
                                'username' => $u['username'],
                                'email' => $u['email'],
                                'first_name' => $u['first_name'],
                                'last_name' => $u['last_name'],
                                'group_id' => $u['group_id']
                            ])); ?>)" class="bg-gray-50 hover:bg-gray-100 text-gray-500 hover:text-gray-700 font-bold py-2 px-4 rounded-xl transition-colors" title="Редактировать">
                                ✏️
                            </button>
                            <?php if ($u['username'] !== 'root'): ?>
                                <button onclick="deleteUser(<?= $u['id']; ?>)" class="bg-red-50 hover:bg-red-100 text-red-500 hover:text-red-700 font-bold py-2 px-4 rounded-xl transition-colors" title="<?= lang('admin_btn_delete'); ?>">
                                    🗑️
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Разделитель -->
    <hr class="my-16 border-t-2 border-gray-100">

    <!-- Секция Групп -->
    <div class="flex justify-between items-end mb-10 mt-16">
        <div>
            <h2 class="text-4xl font-black text-gray-800 mb-2">Группы пользователей</h2>
            <p class="text-lg text-gray-500 font-mono">Управление ролями в системе</p>
        </div>
        <button onclick="openAddGroupModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded-xl text-xl shadow-lg transition-colors flex items-center gap-2">
            Добавить группу
        </button>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-16">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 text-lg">
                    <th class="py-5 px-8 font-bold">ID</th>
                    <th class="py-5 px-8 font-bold">Название</th>
                    <th class="py-5 px-8 font-bold">Описание</th>
                    <th class="py-5 px-8 font-bold text-right">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($groups as $g): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-5 px-8 text-xl font-mono text-gray-700"><?= $g['id']; ?></td>
                        <td class="py-5 px-8 text-xl font-bold text-gray-800"><?= htmlspecialchars($g['name'] ?? ''); ?></td>
                        <td class="py-5 px-8 text-xl text-gray-500"><?= htmlspecialchars($g['description'] ?? ''); ?></td>
                        <td class="py-5 px-8 text-right space-x-2">
                            <button onclick="openEditGroupModal(<?= htmlspecialchars(json_encode($g)); ?>)" class="bg-gray-50 hover:bg-gray-100 text-gray-500 hover:text-gray-700 font-bold py-2 px-4 rounded-xl transition-colors">✏️</button>
                            <?php if ($g['id'] != 1 && $g['id'] != 2): ?>
                                <button onclick="deleteGroup(<?= $g['id']; ?>)" class="bg-red-50 hover:bg-red-100 text-red-500 hover:text-red-700 font-bold py-2 px-4 rounded-xl transition-colors">🗑️</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Разделитель -->
    <hr class="my-16 border-t-2 border-gray-100">

    <!-- Секция бэкапов -->
    <div class="flex justify-between items-end mb-10">
        <div>
            <h2 class="text-4xl font-black text-gray-800 mb-2"><?= lang('admin_backup_title'); ?></h2>
            <p class="text-lg text-gray-500 font-mono"><?= lang('admin_sys_info'); ?> <span class="font-bold text-purple-600"><?= $sys_space; ?></span></p>
        </div>
        <button onclick="createBackup()" id="btnCreateBackup" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded-xl text-xl shadow-lg transition-colors flex items-center gap-2">
            <?= lang('admin_btn_create_backup'); ?>
        </button>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-16">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 text-lg">
                    <th class="py-5 px-8 font-bold"><?= lang('admin_col_filename'); ?></th>
                    <th class="py-5 px-8 font-bold"><?= lang('admin_col_size'); ?></th>
                    <th class="py-5 px-8 font-bold"><?= lang('admin_col_date'); ?></th>
                    <th class="py-5 px-8 font-bold text-right">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($backups)): ?>
                    <tr>
                        <td colspan="4" class="py-10 px-8 text-center text-xl text-gray-500">Нет сохраненных бэкапов.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($backups as $b): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-5 px-8 text-xl font-mono text-gray-700"><?= htmlspecialchars($b['filename'] ?? ''); ?></td>
                            <td class="py-5 px-8 text-xl text-gray-500 font-bold"><?= htmlspecialchars($b['size'] ?? ''); ?></td>
                            <td class="py-5 px-8 text-xl text-gray-500"><?= htmlspecialchars($b['date'] ?? ''); ?></td>
                            <td class="py-5 px-8 text-right">
                                <button onclick="deleteBackup('<?= htmlspecialchars($b['filename'] ?? ''); ?>')" class="bg-red-50 hover:bg-red-100 text-red-500 hover:text-red-700 font-bold py-2 px-6 rounded-xl transition-colors" title="<?= lang('admin_btn_delete_file'); ?>">
                                    <?= lang('admin_btn_delete_file'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Разделитель -->
    <hr class="my-16 border-t-2 border-gray-100">

    <!-- Системные Настройки -->
    <div class="mb-10">
        <h2 class="text-4xl font-black text-gray-800 mb-2">Настройки Системы</h2>
        <p class="text-lg text-gray-500 font-mono">Глобальные параметры работы трекера</p>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-10 mb-16">
        <div class="max-w-xl space-y-10">
            <div>
                <label class="block text-gray-700 text-xl font-bold mb-4">Лимит времени на паузе (минуты)</label>
                <p class="text-gray-500 mb-6">Если таймер находится на паузе дольше указанного времени, сессия будет автоматически остановлена (завершена). Укажите 0, чтобы отключить авто-стоп.</p>
                <input type="number" id="settingPauseLimit" value="<?= htmlspecialchars($pause_limit_minutes ?? 10); ?>" class="w-32 bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-2xl font-bold text-center focus:ring-2 focus:ring-blue-500 focus:outline-none" min="0">
            </div>

            <div>
                <label class="block text-gray-700 text-xl font-bold mb-4">Количество строк на странице (пагинация)</label>
                <p class="text-gray-500 mb-6">Количество записей, отображаемых по умолчанию на страницах журнала, списка задач и заказчиков.</p>
                <select id="settingPerPage" class="w-48 bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl font-bold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="10" <?= (isset($per_page) && $per_page == 10) ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?= (!isset($per_page) || $per_page == 25) ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?= (isset($per_page) && $per_page == 50) ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?= (isset($per_page) && $per_page == 100) ? 'selected' : ''; ?>>100</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 text-xl font-bold mb-4">Директория загрузки файлов ТЗ</label>
                <p class="text-gray-500 mb-6">Абсолютный путь к папке на сервере, куда будут сохраняться загруженные файлы. По умолчанию: <code>uploads/specs/</code> (путь относительно корня сайта).</p>
                <input type="text" id="settingUploadDir" value="<?= htmlspecialchars($upload_dir_setting ?? 'uploads/specs/'); ?>" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <button onclick="saveSettings()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-8 rounded-xl text-xl shadow-md transition-colors">
                    Сохранить настройки
                </button>
            </div>
        </div>
    </div>

</div>

<!-- Модальное окно пользователя (Добавление/Редактирование) с z-index фиксом и скроллом для вертикальных экранов -->
<div id="userModal" class="hidden fixed inset-0 z-[99999] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl p-10 transform transition-all overflow-y-auto max-h-[90vh]">
        <h3 id="userModalTitle" class="text-3xl font-black mb-8 text-gray-800">Пользователь</h3>
        
        <input type="hidden" id="editUserId" value="">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-gray-700 text-lg font-bold mb-2">Логин *</label>
                <input type="text" id="editUsername" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="min 4 chars">
            </div>
            <div>
                <label class="block text-gray-700 text-lg font-bold mb-2">Пароль</label>
                <input type="password" id="editPassword" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Заполните для изменения">
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-lg font-bold mb-2">Email</label>
            <input type="email" id="editEmail" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-gray-700 text-lg font-bold mb-2">Имя</label>
                <input type="text" id="editFirstName" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-gray-700 text-lg font-bold mb-2">Фамилия</label>
                <input type="text" id="editLastName" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
        </div>

        <div class="mb-8">
            <label class="block text-gray-700 text-lg font-bold mb-2">Группа</label>
            <select id="editGroupId" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <?php foreach ($groups as $g): ?>
                    <option value="<?= $g['id']; ?>"><?= htmlspecialchars($g['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex gap-4">
            <button onclick="saveUser()" class="flex-grow bg-blue-600 hover:bg-blue-700 text-white font-bold py-5 px-8 rounded-xl text-xl shadow-lg transition-colors">
                <?= lang('btn_save'); ?>
            </button>
            <button onclick="closeUserModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-5 px-8 rounded-xl text-xl transition-colors">
                <?= lang('btn_cancel'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Модальное окно Группы с z-index фиксом и скроллом для вертикальных экранов -->
<div id="groupModal" class="hidden fixed inset-0 z-[99999] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-10 transform transition-all max-h-[90vh] overflow-y-auto">
        <h3 id="groupModalTitle" class="text-3xl font-black mb-8 text-gray-800">Группа</h3>
        
        <input type="hidden" id="editGroupIdVal" value="">
        <div class="space-y-6 mb-8">
            <div>
                <label class="block text-gray-700 text-lg font-bold mb-2">Название *</label>
                <input type="text" id="editGroupName" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-gray-700 text-lg font-bold mb-2">Описание</label>
                <input type="text" id="editGroupDesc" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
        </div>

        <div class="flex gap-4">
            <button onclick="saveGroup()" class="flex-grow bg-blue-600 hover:bg-blue-700 text-white font-bold py-5 px-8 rounded-xl text-xl shadow-lg transition-colors">
                <?= lang('btn_save'); ?>
            </button>
            <button onclick="closeGroupModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-5 px-8 rounded-xl text-xl transition-colors">
                <?= lang('btn_cancel'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Модальное окно смены пароля с z-index фиксом и скроллом для вертикальных экранов -->
<div id="changePasswordModal" class="hidden fixed inset-0 z-[99999] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-10 transform transition-all max-h-[90vh] overflow-y-auto">
        <h3 class="text-3xl font-black mb-8 text-gray-800"><?= lang('admin_btn_change_password'); ?></h3>
        <input type="hidden" id="changePassUserId" value="">
        
        <div class="space-y-6 mb-8">
            <div>
                <label class="block text-gray-700 text-lg font-bold mb-2"><?= lang('admin_lbl_new_password'); ?></label>
                <input type="password" id="changePasswordVal" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="min 6 chars">
            </div>
            <div>
                <label class="block text-gray-700 text-lg font-bold mb-2"><?= lang('admin_lbl_repeat_password'); ?></label>
                <input type="password" id="changePasswordConf" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="min 6 chars">
            </div>
        </div>

        <div class="flex gap-4">
            <button onclick="saveNewPassword()" class="flex-grow bg-purple-600 hover:bg-purple-700 text-white font-bold py-5 px-8 rounded-xl text-xl shadow-lg transition-colors">
                <?= lang('btn_save'); ?>
            </button>
            <button onclick="closeChangePasswordModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-5 px-8 rounded-xl text-xl transition-colors">
                <?= lang('btn_cancel'); ?>
            </button>
        </div>
    </div>
</div>

<script>
    const api = {
        add_user: '<?php echo site_url("admin/add_user_ajax"); ?>',
        edit_user: '<?php echo site_url("admin/edit_user_ajax"); ?>',
        delete_user: '<?php echo site_url("admin/delete_user_ajax"); ?>',
        change_password: '<?php echo site_url("admin/change_password_ajax"); ?>',
        create_backup: '<?php echo site_url("admin/backup_db_ajax"); ?>',
        delete_backup: '<?php echo site_url("admin/delete_backup_ajax"); ?>',
        add_group: '<?php echo site_url("admin/add_group_ajax"); ?>',
        edit_group: '<?php echo site_url("admin/edit_group_ajax"); ?>',
        delete_group: '<?php echo site_url("admin/delete_group_ajax"); ?>'
    };

    function openAddUserModal() {
        $('#userModalTitle').text('Добавить пользователя');
        $('#editUserId').val('');
        $('#editUsername').val('').prop('disabled', false);
        $('#editPassword').val('');
        $('#editEmail').val('');
        $('#editFirstName').val('');
        $('#editLastName').val('');
        $('#editGroupId').val('2');
        $('#userModal').removeClass('hidden');
    }

    function openEditUserModal(user) {
        $('#userModalTitle').text('Редактировать пользователя');
        $('#editUserId').val(user.id);
        $('#editUsername').val(user.username).prop('disabled', true);
        $('#editPassword').val('');
        $('#editEmail').val(user.email);
        $('#editFirstName').val(user.first_name);
        $('#editLastName').val(user.last_name);
        $('#editGroupId').val(user.group_id);
        $('#userModal').removeClass('hidden');
    }

    function closeUserModal() {
        $('#userModal').addClass('hidden');
    }

    function saveUser() {
        const id = $('#editUserId').val();
        const data = {
            username: $('#editUsername').val().trim(),
            password: $('#editPassword').val().trim(),
            email: $('#editEmail').val().trim(),
            first_name: $('#editFirstName').val().trim(),
            last_name: $('#editLastName').val().trim(),
            group_id: $('#editGroupId').val()
        };

        if (!id && (!data.username || !data.password)) {
            alert('Заполните логин и пароль!');
            return;
        }
        
        if (id) data.user_id = id;
        
        const endpoint = id ? api.edit_user : api.add_user;

        $.post(endpoint, data, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                window.location.reload();
            } else {
                alert(res.message);
            }
        });
    }

    function deleteUser(id) {
        if (confirm("Вы уверены, что хотите удалить этого пользователя? Все его задачи и время будут стерты навсегда!")) {
            $.post(api.delete_user, { user_id: id }, function(response) {
                let res = JSON.parse(response);
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    alert(res.message);
                }
            });
        }
    }

    // --- ГРУППЫ ---
    function openAddGroupModal() {
        $('#groupModalTitle').text('Добавить группу');
        $('#editGroupIdVal').val('');
        $('#editGroupName').val('');
        $('#editGroupDesc').val('');
        $('#groupModal').removeClass('hidden');
    }

    function openEditGroupModal(group) {
        $('#groupModalTitle').text('Редактировать группу');
        $('#editGroupIdVal').val(group.id);
        $('#editGroupName').val(group.name);
        $('#editGroupDesc').val(group.description);
        $('#groupModal').removeClass('hidden');
    }

    function closeGroupModal() {
        $('#groupModal').addClass('hidden');
    }

    function saveGroup() {
        const id = $('#editGroupIdVal').val();
        const data = {
            name: $('#editGroupName').val().trim(),
            description: $('#editGroupDesc').val().trim()
        };
        
        if (!data.name) {
            alert('Заполните название!');
            return;
        }
        
        if (id) data.group_id = id;
        
        const endpoint = id ? api.edit_group : api.add_group;

        $.post(endpoint, data, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                window.location.reload();
            } else {
                alert(res.message);
            }
        });
    }

    function deleteGroup(id) {
        if (confirm("Удалить группу? Это может повлиять на пользователей!")) {
            $.post(api.delete_group, { group_id: id }, function(response) {
                let res = JSON.parse(response);
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    alert(res.message);
                }
            });
        }
    }

    // --- Смена пароля ---
    function openChangePasswordModal(userId) {
        $('#changePassUserId').val(userId);
        $('#changePasswordVal').val('');
        $('#changePasswordConf').val('');
        $('#changePasswordModal').removeClass('hidden');
    }

    function closeChangePasswordModal() {
        $('#changePasswordModal').addClass('hidden');
    }

    function saveNewPassword() {
        const userId = $('#changePassUserId').val();
        const pass = $('#changePasswordVal').val().trim();
        const conf = $('#changePasswordConf').val().trim();

        if (pass.length < 6) {
            alert('<?= lang("admin_err_short_password"); ?>');
            return;
        }
        if (pass !== conf) {
            alert('<?= lang("admin_err_passwords_mismatch"); ?>');
            return;
        }

        $.post(api.change_password, { user_id: userId, password: pass, passconf: conf }, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                alert(res.message);
                closeChangePasswordModal();
            } else {
                alert(res.message);
            }
        });
    }

    // --- Бэкапы ---
    function createBackup() {
        const btn = $('#btnCreateBackup');
        const originalText = btn.html();
        btn.html('⏳ Создание...').prop('disabled', true).addClass('opacity-50');

        $.post(api.create_backup, {}, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                window.location.reload();
            } else {
                alert(res.message + (res.debug ? "\n\n" + res.debug : ""));
                btn.html(originalText).prop('disabled', false).removeClass('opacity-50');
            }
        }).fail(function() {
            alert('Произошла системная ошибка при вызове скрипта бэкапа.');
            btn.html(originalText).prop('disabled', false).removeClass('opacity-50');
        });
    }

    function deleteBackup(filename) {
        if (!confirm('<?= lang("admin_js_confirm_delete_backup"); ?>')) return;

        $.post('<?= base_url("admin/delete_backup_ajax"); ?>', { filename: filename }, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                window.location.reload();
            } else {
                alert(res.message);
            }
        });
    }

    function saveSettings() {
        const limit = $('#settingPauseLimit').val();
        const perPage = $('#settingPerPage').val();
        const uploadDir = $('#settingUploadDir').val();
        $.post('<?= base_url("admin/save_settings_ajax"); ?>', { pause_limit_minutes: limit, per_page: perPage, upload_dir: uploadDir }, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                alert(res.message);
            } else {
                alert(res.message);
            }
        });
    }
</script>
