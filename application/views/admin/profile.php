<!-- Страница профиля пользователя -->

<!-- Заголовок -->
<h1 class="text-4xl font-black text-gray-800 mb-8">👤 Мой профиль</h1>

<div class="max-w-2xl space-y-8">

    <!-- ============================================================ -->
    <!-- Карточка с информацией о профиле (только чтение)             -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Информация</h2>
        <dl class="space-y-4 text-sm">
            <!-- Логин -->
            <div class="flex justify-between">
                <dt class="font-semibold text-gray-500">Логин</dt>
                <dd class="text-gray-800 font-semibold"><?= htmlspecialchars($profile['username']) ?></dd>
            </div>
            <!-- Email -->
            <div class="flex justify-between">
                <dt class="font-semibold text-gray-500">Email</dt>
                <dd class="text-gray-800"><?= htmlspecialchars($profile['email']) ?></dd>
            </div>
            <!-- Имя и фамилия -->
            <div class="flex justify-between">
                <dt class="font-semibold text-gray-500">Имя</dt>
                <dd class="text-gray-800"><?= htmlspecialchars($profile['first_name']) ?> <?= htmlspecialchars($profile['last_name']) ?></dd>
            </div>
            <!-- Группа (роль) -->
            <div class="flex justify-between">
                <dt class="font-semibold text-gray-500">Группа</dt>
                <dd>
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                        <?= htmlspecialchars($profile['group_name']) ?>
                    </span>
                </dd>
            </div>
            <!-- Пол -->
            <div class="flex justify-between">
                <dt class="font-semibold text-gray-500">Пол</dt>
                <dd class="text-gray-800">
                    <?php
                        $gender_labels = ['male' => 'Мужчина 👨', 'female' => 'Женщина 👩', 'not_specified' => 'Не указан'];
                        echo $gender_labels[$profile['gender'] ?? 'not_specified'] ?? 'Не указан';
                    ?>
                </dd>
            </div>
            <!-- Дата регистрации -->
            <div class="flex justify-between">
                <dt class="font-semibold text-gray-500">Дата регистрации</dt>
                <dd class="text-gray-800"><?= htmlspecialchars($profile['created_at']) ?></dd>
            </div>
        </dl>
    </div>

    <!-- ============================================================ -->
    <!-- Форма редактирования профиля                                 -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Редактирование профиля</h2>
        <div class="space-y-5">
            <!-- Email -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                <input type="email" id="profile_email"
                    value="<?= htmlspecialchars($profile['email']) ?>"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors text-sm">
            </div>
            <!-- Имя -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Имя</label>
                <input type="text" id="profile_first_name"
                    value="<?= htmlspecialchars($profile['first_name']) ?>"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors text-sm">
            </div>
            <!-- Фамилия -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Фамилия</label>
                <input type="text" id="profile_last_name"
                    value="<?= htmlspecialchars($profile['last_name']) ?>"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors text-sm">
            </div>
            <!-- Пол — карточки выбора -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-3">Пол</label>
                <div class="flex gap-3" id="gender-cards">

                    <!-- Мужчина -->
                    <label class="gender-card flex-1 cursor-pointer" data-gender="male">
                        <input type="radio" name="profile_gender" id="gender_male" value="male"
                            class="sr-only"
                            <?= ($profile['gender'] ?? '') === 'male' ? 'checked' : '' ?>>
                        <div class="gender-card-inner gender-male rounded-2xl p-4 text-center select-none">
                            <div class="text-4xl mb-2 transition-transform duration-200 gender-icon">👨</div>
                            <div class="text-sm font-bold tracking-wide">Мужчина</div>
                        </div>
                    </label>

                    <!-- Женщина -->
                    <label class="gender-card flex-1 cursor-pointer" data-gender="female">
                        <input type="radio" name="profile_gender" id="gender_female" value="female"
                            class="sr-only"
                            <?= ($profile['gender'] ?? '') === 'female' ? 'checked' : '' ?>>
                        <div class="gender-card-inner gender-female rounded-2xl p-4 text-center select-none">
                            <div class="text-4xl mb-2 transition-transform duration-200 gender-icon">👩</div>
                            <div class="text-sm font-bold tracking-wide">Женщина</div>
                        </div>
                    </label>

                    <!-- Не указано -->
                    <label class="gender-card flex-1 cursor-pointer" data-gender="not_specified">
                        <input type="radio" name="profile_gender" id="gender_ns" value="not_specified"
                            class="sr-only"
                            <?= (($profile['gender'] ?? 'not_specified') === 'not_specified') ? 'checked' : '' ?>>
                        <div class="gender-card-inner gender-ns rounded-2xl p-4 text-center select-none">
                            <div class="text-4xl mb-2 transition-transform duration-200 gender-icon">🧑</div>
                            <div class="text-sm font-bold tracking-wide text-gray-400">Не указано</div>
                        </div>
                    </label>

                </div>
            </div>

            <style>
            /* ==============================
               КАРТОЧКИ ВЫБОРА ПОЛА
               ============================== */
            .gender-card-inner {
                border: 2px solid #e5e7eb;
                transition: border-color 0.2s ease, background 0.2s ease,
                            box-shadow 0.2s ease, transform 0.15s ease;
                background: #f9fafb;
                color: #6b7280;
            }

            /* Hover — лёгкий подъём */
            .gender-card:hover .gender-card-inner {
                transform: translateY(-3px);
                box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            }
            .gender-card:hover .gender-icon {
                transform: scale(1.15);
            }

            /* Мужчина: hover */
            .gender-card:hover .gender-male {
                border-color: #93c5fd;
                background: #eff6ff;
                color: #1d4ed8;
            }
            /* Мужчина: выбран */
            .gender-card input:checked ~ .gender-male {
                border-color: #3b82f6;
                background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
                color: #1d4ed8;
                box-shadow: 0 0 0 3px rgba(59,130,246,0.15),
                            0 8px 24px rgba(59,130,246,0.15);
                transform: translateY(-2px);
            }

            /* Женщина: hover */
            .gender-card:hover .gender-female {
                border-color: #f9a8d4;
                background: #fdf2f8;
                color: #be185d;
            }
            /* Женщина: выбрана */
            .gender-card input:checked ~ .gender-female {
                border-color: #ec4899;
                background: linear-gradient(135deg, #fce7f3 0%, #fdf2f8 100%);
                color: #be185d;
                box-shadow: 0 0 0 3px rgba(236,72,153,0.15),
                            0 8px 24px rgba(236,72,153,0.12);
                transform: translateY(-2px);
            }

            /* Не указано: hover */
            .gender-card:hover .gender-ns {
                border-color: #9ca3af;
                background: #f3f4f6;
                color: #374151;
            }
            /* Не указано: выбрано */
            .gender-card input:checked ~ .gender-ns {
                border-color: #6b7280;
                background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
                color: #374151;
                box-shadow: 0 0 0 3px rgba(107,114,128,0.15),
                            0 8px 24px rgba(107,114,128,0.08);
                transform: translateY(-2px);
            }

            /* Нажатие (active) */
            .gender-card:active .gender-card-inner {
                transform: translateY(0) scale(0.97);
                box-shadow: none;
            }
            </style>
            <!-- Кнопка сохранения профиля -->
            <div class="pt-2">
                <button onclick="saveProfile()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded-xl text-xl shadow-lg transition-colors">
                    💾 Сохранить
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- Секция смены пароля                                          -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">🔒 Сменить пароль</h2>
        <div class="space-y-5">
            <!-- Текущий пароль -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Текущий пароль</label>
                <input type="password" id="current_password"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors text-sm"
                    placeholder="Введите текущий пароль">
            </div>
            <!-- Новый пароль -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Новый пароль</label>
                <input type="password" id="new_password"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors text-sm"
                    placeholder="Введите новый пароль">
            </div>
            <!-- Повтор нового пароля -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Повторите новый пароль</label>
                <input type="password" id="confirm_password"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors text-sm"
                    placeholder="Повторите новый пароль">
            </div>
            <!-- Кнопка смены пароля -->
            <div class="pt-2">
                <button onclick="changeMyPassword()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded-xl text-xl shadow-lg transition-colors">
                    🔑 Сменить пароль
                </button>
            </div>
        </div>
    </div>

</div><!-- /max-w-2xl -->

<script>
/**
 * Сохранение данных профиля через AJAX (jQuery)
 */
function saveProfile() {
    $.post('<?= site_url("admin/save_profile_ajax") ?>', {
        email:      $('#profile_email').val().trim(),
        first_name: $('#profile_first_name').val().trim(),
        last_name:  $('#profile_last_name').val().trim(),
        // Пол пользователя
        gender:     $('input[name="profile_gender"]:checked').val() || 'not_specified'
    }, function(response) {
        let res = (typeof response === 'string') ? JSON.parse(response) : response;
        alert(res.message || 'Профиль сохранён');
        if (res.status === 'success') location.reload();
    }).fail(function() {
        alert('Ошибка при сохранении профиля');
    });
}

/**
 * Смена пароля пользователя с клиентской валидацией
 */
function changeMyPassword() {
    var current = $('#current_password').val();
    var newPass  = $('#new_password').val();
    var confirmPass = $('#confirm_password').val();

    // Проверка заполненности полей
    if (!current || !newPass || !confirmPass) {
        alert('Заполните все поля');
        return;
    }
    // Проверка совпадения паролей
    if (newPass !== confirmPass) {
        alert('Новый пароль и подтверждение не совпадают');
        return;
    }
    // Минимальная длина пароля
    if (newPass.length < 6) {
        alert('Новый пароль должен содержать не менее 6 символов');
        return;
    }

    $.post('<?= site_url("admin/change_my_password_ajax") ?>', {
        current_password:     current,
        new_password:         newPass,
        new_password_confirm: confirmPass
    }, function(response) {
        let res = (typeof response === 'string') ? JSON.parse(response) : response;
        alert(res.message || 'Пароль изменён');
        if (res.status === 'success') {
            // Очищаем поля после успешной смены
            $('#current_password, #new_password, #confirm_password').val('');
        }
    }).fail(function() {
        alert('Ошибка при смене пароля');
    });
}
</script>
