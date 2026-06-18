<div class="bg-white p-10 rounded-2xl shadow-xl w-full max-w-2xl mx-auto mt-10 mb-20">
    <h2 class="text-3xl font-black mb-8 text-gray-800 border-b pb-4">Мой профиль</h2>

    <div id="profile-errors" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert"></div>

    <?php echo form_open('profile/save_ajax', ['id' => 'profile-form', 'class' => 'space-y-6']); ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label for="username" class="block text-gray-700 text-lg font-bold mb-2">Логин (нельзя изменить)</label>
                <input type="text" id="username" class="w-full bg-gray-100 border border-gray-300 rounded-xl px-5 py-4 text-xl text-gray-500 cursor-not-allowed" value="<?= html_escape($user['username']); ?>" disabled>
            </div>

            <div>
                <label for="email" class="block text-gray-700 text-lg font-bold mb-2">Email *</label>
                <input type="email" id="email" name="email" autocomplete="username" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" value="<?= html_escape($user['email']); ?>" required>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="first_name" class="block text-gray-700 text-lg font-bold mb-2">Имя</label>
                <input type="text" id="first_name" name="first_name" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" value="<?= html_escape($user['first_name']); ?>">
            </div>

            <div>
                <label for="last_name" class="block text-gray-700 text-lg font-bold mb-2">Фамилия</label>
                <input type="text" id="last_name" name="last_name" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" value="<?= html_escape($user['last_name']); ?>">
        </div>

        <div class="border-t pt-6 mt-8">
            <h3 class="text-xl font-bold mb-4 text-gray-800">Настройки хранилища</h3>
            <div>
                <label for="upload_dir" class="block text-gray-700 text-lg font-bold mb-2">Директория хранения файлов ТЗ *</label>
                <input type="text" id="upload_dir" name="upload_dir" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none font-mono" value="<?= html_escape($upload_dir); ?>" required>
                <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                    Укажите путь для загрузки файлов ТЗ. Можно использовать абсолютный путь (начинается с <code>/</code>, например <code>/mnt/share/time_uploads/</code>) для подключения сетевых дисков, или относительный путь от корня проекта (например, <code>uploads/specs/</code>).
                </p>
            </div>
        </div>

        <div class="border-t pt-6 mt-8">
            <h3 class="text-xl font-bold mb-4 text-gray-800">Смена пароля (опционально)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="password" class="block text-gray-700 text-lg font-bold mb-2">Новый пароль</label>
                    <input type="password" id="password" name="password" autocomplete="new-password" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Оставьте пустым для сохранения">
                </div>

                <div>
                    <label for="passconf" class="block text-gray-700 text-lg font-bold mb-2">Повторите новый пароль</label>
                    <input type="password" id="passconf" name="passconf" autocomplete="new-password" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>
        </div>



        <div class="pt-6">
            <button type="submit" class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-10 rounded-xl text-xl shadow-lg transition-colors flex justify-center items-center">
                <span>Сохранить профиль</span>
                <svg class="w-6 h-6 ml-2 hidden spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2.5"></circle><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6l3.5 2" class="anim-spin-slow" style="transform-origin: 12px 12px;"></path></svg>
            </button>
        </div>

    <?php echo form_close(); ?>
</div>
