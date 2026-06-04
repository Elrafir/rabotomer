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
                            <?php if ($u['username'] === 'root'): ?>
                                <span class="bg-purple-100 text-purple-700 px-4 py-1 rounded-full font-bold text-sm uppercase tracking-wide">Admin</span>
                            <?php else: ?>
                                <span class="bg-blue-100 text-blue-700 px-4 py-1 rounded-full font-bold text-sm uppercase tracking-wide">User</span>
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

</div>

<!-- Модальное окно добавления пользователя -->
<div id="addUserModal" class="hidden fixed inset-0 z-[100] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-10 transform transition-all">
        <h3 class="text-3xl font-black mb-8 text-gray-800"><?= lang('admin_btn_add_user'); ?></h3>
        
        <div class="space-y-6 mb-8">
            <div>
                <label class="block text-gray-700 text-lg font-bold mb-2"><?= lang('admin_col_login'); ?></label>
                <input type="text" id="newUsername" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="min 4, a-z 0-9">
            </div>
            <div>
                <label class="block text-gray-700 text-lg font-bold mb-2"><?= lang('admin_col_password'); ?></label>
                <input type="password" id="newPassword" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="min 6 chars">
            </div>
        </div>

        <div class="flex gap-4">
            <button onclick="saveUser()" class="flex-grow bg-blue-600 hover:bg-blue-700 text-white font-bold py-5 px-8 rounded-xl text-xl shadow-lg transition-colors">
                <?= lang('btn_save'); ?>
            </button>
            <button onclick="closeAddUserModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-5 px-8 rounded-xl text-xl transition-colors">
                <?= lang('btn_cancel'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Модальное окно смены пароля -->
<div id="changePasswordModal" class="hidden fixed inset-0 z-[100] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-10 transform transition-all">
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
        delete_user: '<?php echo site_url("admin/delete_user_ajax"); ?>',
        change_password: '<?php echo site_url("admin/change_password_ajax"); ?>',
        create_backup: '<?php echo site_url("admin/backup_db_ajax"); ?>',
        delete_backup: '<?php echo site_url("admin/delete_backup_ajax"); ?>'
    };

    function openAddUserModal() {
        $('#newUsername').val('');
        $('#newPassword').val('');
        $('#addUserModal').removeClass('hidden');
    }

    function closeAddUserModal() {
        $('#addUserModal').addClass('hidden');
    }

    function saveUser() {
        const username = $('#newUsername').val().trim();
        const password = $('#newPassword').val().trim();

        if (!username || !password) {
            alert('Заполните все поля!');
            return;
        }

        $.post(api.add_user, { username: username, password: password }, function(response) {
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
        if (confirm("Точно удалить этот файл бэкапа?")) {
            $.post(api.delete_backup, { filename: filename }, function(response) {
                let res = JSON.parse(response);
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    alert(res.message);
                }
            });
        }
    }
</script>
