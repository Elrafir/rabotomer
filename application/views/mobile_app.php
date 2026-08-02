<div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-8 py-12 text-center text-white">
            <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl mb-4">
                Нативные приложения
            </h1>
            <p class="mt-4 text-xl max-w-2xl mx-auto">
                Работомер всегда под рукой! Получите полный контроль над задачами с помощью удобных приложений для Windows и Android.
            </p>
        </div>
        
        <div class="px-8 py-12 bg-white flex flex-col items-center">
            <p class="text-xl text-gray-600 mb-12 max-w-2xl text-center">
                Мы подготовили для вас отдельные версии трекера, которые глубоко интегрируются в систему. Плавающие виджеты, оффлайн-режим и глобальные горячие клавиши — управляйте временем с максимальным комфортом!
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl w-full">
                
                <!-- Android Card -->
                <div class="bg-white rounded-3xl shadow-xl overflow-hidden transform hover:-translate-y-2 transition-all duration-300">
                    <div class="h-32 bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center">
                        <svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M17.6 9.48l1.84-3.18c.16-.31.04-.69-.26-.85-.29-.15-.65-.06-.81.24l-1.92 3.32C15.11 8.55 13.61 8.25 12 8.25s-3.11.3-4.45.78L5.63 5.71c-.16-.3-.52-.39-.81-.24-.3.16-.42.54-.26.85l1.84 3.18C3.59 11.23 1.61 13.89 1.15 17h21.7c-.46-3.11-2.44-5.77-5.25-7.52zM7 14.25c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm10 0c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/></svg>
                    </div>
                    <div class="p-8 text-left">
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">Для Android</h3>
                        <p class="text-gray-600 mb-6 h-20">Всегда под рукой. Плавающий пузырек-виджет с таймером будет отображаться поверх любых окон и приложений.</p>
                        <a href="<?= site_url('MobileApp/download/android') ?>" download target="_blank" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-green-600 hover:bg-green-700 shadow-lg hover:shadow-xl transition-all w-full">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Скачать APK
                        </a>
                    </div>
                </div>

                <!-- Windows Card -->
                <div class="bg-white rounded-3xl shadow-xl overflow-hidden transform hover:-translate-y-2 transition-all duration-300">
                    <div class="h-32 bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                        <svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M0 3.449L9.75 2.1v9.451H0m10.949-9.602L24 0v11.4H10.949M0 12.6h9.75v9.451L0 20.699M10.949 12.6H24V24l-12.951-1.801"/></svg>
                    </div>
                    <div class="p-8 text-left">
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">Для Windows</h3>
                        <p class="text-gray-600 mb-6 h-20">Управление из трея, глобальные горячие клавиши (Ctrl+Shift+T) и прозрачный плавающий таймер поверх окон.</p>
                        <a href="<?= site_url('MobileApp/download/windows') ?>" download target="_blank" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-blue-600 hover:bg-blue-700 shadow-lg hover:shadow-xl transition-all w-full">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Скачать Windows
                        </a>
                    </div>
                </div>

            </div>

            <div class="mt-16 bg-white p-8 rounded-2xl shadow-sm border border-gray-100 max-w-4xl w-full text-left">
                <h3 class="text-3xl font-extrabold text-gray-900 mb-8 flex items-center">
                    <span class="bg-blue-100 text-blue-800 text-sm py-1 px-3 rounded-full mr-4">FAQ</span> Вопросы и ответы
                </h3>
                
                <div class="space-y-4">
                    
                    <!-- Вопрос 1 -->
                    <details class="group border border-gray-200 rounded-xl overflow-hidden" id="install-android">
                        <summary class="flex justify-between items-center font-medium cursor-pointer list-none p-5 text-lg text-gray-900 bg-gray-50 hover:bg-gray-100 transition-colors">
                            <span>Как установить и обновить приложение на Android?</span>
                            <span class="transition group-open:rotate-180">
                                <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="24"><path d="M6 9l6 6 6-6"></path></svg>
                            </span>
                        </summary>
                        <div class="text-gray-600 mt-3 p-5 pt-0 leading-relaxed border-t border-gray-100">
                            <p class="mb-2">1. Нажмите кнопку <strong>"Скачать APK"</strong> выше и дождитесь окончания загрузки файла.</p>
                            <p class="mb-2">2. Откройте скачанный файл `Работомер.apk`.</p>
                            <p class="mb-2">3. Если система запросит разрешение на установку из неизвестных источников, перейдите в настройки и разрешите установку для вашего браузера.</p>
                            <p class="mb-2">4. Нажмите "Установить".</p>
                            <p class="mt-4 font-semibold text-gray-800">Обновление:</p>
                            <p>Приложение само проверит наличие новой версии при запуске и предложит скачать свежий APK-файл. Просто скачайте его и установите поверх старой версии — все ваши данные сохранятся.</p>
                        </div>
                    </details>
                    
                    <!-- Вопрос 2 -->
                    <details class="group border border-gray-200 rounded-xl overflow-hidden" id="install-windows">
                        <summary class="flex justify-between items-center font-medium cursor-pointer list-none p-5 text-lg text-gray-900 bg-gray-50 hover:bg-gray-100 transition-colors">
                            <span>Как установить версию для Windows?</span>
                            <span class="transition group-open:rotate-180">
                                <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="24"><path d="M6 9l6 6 6-6"></path></svg>
                            </span>
                        </summary>
                        <div class="text-gray-600 mt-3 p-5 pt-0 leading-relaxed border-t border-gray-100">
                            <p class="mb-2">Версия для Windows поставляется в портативном виде, её не нужно устанавливать в систему:</p>
                            <p class="mb-2">1. Нажмите <strong>"Скачать Windows"</strong> и сохраните `.zip` архив.</p>
                            <p class="mb-2">2. Распакуйте содержимое архива в любую удобную папку на вашем компьютере (например, `C:\Program Files\Работомер`).</p>
                            <p class="mb-2">3. Для запуска программы откройте файл <strong>Работомер.exe</strong>.</p>
                            <p class="mt-4 text-sm text-gray-500">Совет: Вы можете нажать правой кнопкой мыши на `Работомер.exe` и выбрать "Отправить -> На рабочий стол (создать ярлык)" для быстрого доступа.</p>
                        </div>
                    </details>

                    <!-- Вопрос 3 -->
                    <details class="group border border-gray-200 rounded-xl overflow-hidden" id="features">
                        <summary class="flex justify-between items-center font-medium cursor-pointer list-none p-5 text-lg text-gray-900 bg-gray-50 hover:bg-gray-100 transition-colors">
                            <span>Какие уникальные функции есть в нативных приложениях?</span>
                            <span class="transition group-open:rotate-180">
                                <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="24"><path d="M6 9l6 6 6-6"></path></svg>
                            </span>
                        </summary>
                        <div class="text-gray-600 mt-3 p-5 pt-0 leading-relaxed border-t border-gray-100">
                            <ul class="list-disc list-inside space-y-3">
                                <li><strong>Автономная работа:</strong> Все ваши задачи сохраняются на устройстве. При появлении интернета они автоматически отправятся на сервер.</li>
                                <li><strong>Виджет-пузырек (Android и Windows):</strong> Таймер может отображаться в виде маленького плавающего кружочка поверх всех остальных окон, чтобы вы всегда видели, сколько времени потрачено.</li>
                                <li><strong>Глобальные горячие клавиши (Windows):</strong> Нажмите <kbd class="bg-gray-100 border border-gray-300 rounded px-2 py-1 text-sm text-gray-800 font-mono">Ctrl</kbd> + <kbd class="bg-gray-100 border border-gray-300 rounded px-2 py-1 text-sm text-gray-800 font-mono">Shift</kbd> + <kbd class="bg-gray-100 border border-gray-300 rounded px-2 py-1 text-sm text-gray-800 font-mono">T</kbd> находясь в любой программе, чтобы моментально запустить или остановить таймер.</li>
                                <li><strong>Управление в трее (Windows):</strong> При закрытии окна приложение сворачивается к часам, откуда можно управлять таймером в два клика.</li>
                            </ul>
                        </div>
                    </details>

                </div>
            </div>
        </div>
    </div>
</div>
