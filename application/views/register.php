<!-- Контейнер формы регистрации -->
<div class="bg-white p-10 rounded-2xl shadow-xl w-full max-w-md mx-auto mt-10">
    
    <!-- Заголовок -->
    <h2 class="text-3xl font-black mb-8 text-center text-gray-800">Регистрация</h2>

    <!-- Контейнер для ошибок валидации -->
    <div id="register-errors" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert"></div>

    <!-- Форма отправляется через AJAX -->
    <?php echo form_open('auth/register_ajax', ['id' => 'register-form', 'class' => 'space-y-6']); ?>
        
        <div>
            <label for="username" class="block text-gray-700 text-lg font-bold mb-2">Логин *</label>
            <input type="text" id="username" name="username" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" required autofocus>
        </div>

        <div>
            <label for="email" class="block text-gray-700 text-lg font-bold mb-2">Email *</label>
            <input type="email" id="email" name="email" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
        </div>

        <div>
            <label for="first_name" class="block text-gray-700 text-lg font-bold mb-2">Имя</label>
            <input type="text" id="first_name" name="first_name" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>

        <div>
            <label for="password" class="block text-gray-700 text-lg font-bold mb-2">Пароль *</label>
            <input type="password" id="password" name="password" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
        </div>

        <div>
            <label for="passconf" class="block text-gray-700 text-lg font-bold mb-2">Повторите пароль *</label>
            <input type="password" id="passconf" name="passconf" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-5 py-4 text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
        </div>

        <div>
            <!-- Кнопка регистрации -->
            <button type="submit" id="btn-register" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-5 px-4 rounded-xl text-2xl shadow-lg transition-colors flex justify-center items-center">
                <span>Зарегистрироваться</span>
                <svg class="w-6 h-6 ml-2 hidden spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2.5"></circle><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6l3.5 2" class="anim-spin-slow" style="transform-origin: 12px 12px;"></path></svg>
            </button>
        </div>

    <?php echo form_close(); ?>

    <!-- Ссылка на авторизацию (AJAX) -->
    <div class="mt-8 text-center">
        <p class="text-gray-600 text-lg">Уже есть аккаунт? 
            <a href="<?= site_url('auth/login'); ?>" class="text-blue-600 font-bold hover:underline ajax-link" onclick="event.preventDefault(); loadAjaxPage(this.href);">
                Войти
            </a>
        </p>
    </div>
</div>
