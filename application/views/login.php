<!-- Контейнер формы входа -->
<div class="bg-white p-10 rounded-2xl shadow-xl w-full max-w-md mx-auto mt-10">
    
    <!-- Заголовок из локализации -->
    <h2 class="text-3xl font-black mb-8 text-center text-gray-800"><?= lang('login_title'); ?></h2>

    <!-- Вывод ошибки, если она есть -->
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-xl relative mb-6" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <!-- Вывод ошибок валидации формы CodeIgniter -->
    <?php if (validation_errors()): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <?php echo validation_errors(); ?>
        </div>
    <?php endif; ?>

    <!-- Форма отправляется методом POST на тот же URL (Auth/login) -->
    <?php echo form_open('auth/login', ['class' => 'space-y-6']); ?>
        
        <div>
            <label for="username" class="block text-gray-700 text-lg font-bold mb-2"><?= lang('login_username_label'); ?></label>
            <input type="text" id="username" name="username" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="<?= lang('login_username_placeholder'); ?>" required autofocus>
        </div>

        <div>
            <label for="password" class="block text-gray-700 text-lg font-bold mb-2"><?= lang('login_password_label'); ?></label>
            <input type="password" id="password" name="password" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="<?= lang('login_password_placeholder'); ?>" required>
        </div>

        <div>
            <!-- Большая и заметная кнопка входа -->
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-5 px-4 rounded-xl text-2xl shadow-lg transition-colors">
                <?= lang('login_submit'); ?>
            </button>
        </div>

    <?php echo form_close(); ?>
</div>
