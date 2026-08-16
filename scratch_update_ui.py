import re

# We will read index.html and update the profileModal and add serverSyncModal
with open('/home/alexey/www/time-android/www/index.html', 'r', encoding='utf-8') as f:
    html = f.read()

# Replace profileModal block with separate profileModal and serverSyncModal
old_modal_pattern = r'<!-- 8\. Модальное окно Личного кабинета / Профиля / Сервера -->[\s\S]*?<!-- 9\. Модальное окно проверки и загрузки обновлений \(#updateModal\) -->'

new_modals = '''<!-- 8. Модальное окно Личного кабинета (#profileModal) -->
    <div id="profileModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4">
            <div class="flex justify-between items-center border-b pb-3">
                <div class="flex items-center gap-3">
                    <img id="profileModalAvatar" src="assets/img/avatar_male.png" class="w-12 h-12 rounded-full border border-gray-200 object-cover shadow-sm">
                    <div>
                        <h3 id="profileModalUsername" class="text-xl font-black text-gray-900">Личный кабинет</h3>
                        <p class="text-xs text-gray-500">Управление профилем и интерфейсом</p>
                    </div>
                </div>
                <button onclick="closeProfileModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold p-1">&times;</button>
            </div>

            <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
                <!-- Блок 1: Пользователь и Аватар -->
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200/80 space-y-3">
                    <h4 class="text-xs font-black uppercase text-gray-500 tracking-wider">Профиль пользователя</h4>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-bold text-gray-800" id="profileDisplayName">Пользователь</div>
                            <div class="text-xs text-gray-500" id="profileUserRole">Администратор / Разработчик</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="user_gender" value="male" checked onchange="setAvatarGender('male')" class="text-blue-600">
                                <img src="assets/img/avatar_male.png" class="w-7 h-7 rounded-full border">
                                <span class="text-xs font-semibold">М</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="user_gender" value="female" onchange="setAvatarGender('female')" class="text-blue-600">
                                <img src="assets/img/avatar_female.png" class="w-7 h-7 rounded-full border">
                                <span class="text-xs font-semibold">Ж</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Блок 2: Модульная кастомизация интерфейса -->
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200/80 space-y-3">
                    <h4 class="text-xs font-black uppercase text-gray-500 tracking-wider">Отображение модулей</h4>
                    
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <div class="text-sm font-bold text-gray-800">Скрыть финансовую информацию</div>
                            <div class="text-xs text-gray-500">Убирает ставки, цены и доходы с экрана</div>
                        </div>
                        <input type="checkbox" id="settingHideFinance" onchange="toggleHideFinance(this.checked)" class="w-5 h-5 text-blue-600 rounded">
                    </label>

                    <label class="flex items-center justify-between cursor-pointer pt-2 border-t border-gray-200/50">
                        <div>
                            <div class="text-sm font-bold text-gray-800">Показывать 24-часовой таймлайн дня</div>
                            <div class="text-xs text-gray-500">Полоса сессий с разбивкой ПК vs Планшет</div>
                        </div>
                        <input type="checkbox" id="settingShowTimeline" checked onchange="toggleShowTimeline(this.checked)" class="w-5 h-5 text-blue-600 rounded">
                    </label>
                </div>

                <!-- Блок 3: Тема оформления -->
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200/80 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-bold text-gray-800">Оформление и цветовая гамма</div>
                        <div class="text-xs text-gray-500">12 тем, тон, насыщенность, прозрачность</div>
                    </div>
                    <button onclick="closeProfileModal(); openThemeModal();" class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-100 text-gray-800 font-bold rounded-xl text-xs shadow-sm flex items-center gap-1.5">
                        <span>🎨</span> Настроить
                    </button>
                </div>

                <!-- Блок 4: Быстрый переход к связи с сервером -->
                <div class="p-4 bg-blue-50/60 rounded-2xl border border-blue-200/80 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-bold text-blue-900">Настройки связи и сервера</div>
                        <div class="text-xs text-blue-700">Выбор IP, хотспот, автопоиск в сети</div>
                    </div>
                    <button onclick="closeProfileModal(); openServerSyncModal();" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-sm flex items-center gap-1.5">
                        <span>📡</span> Связь
                    </button>
                </div>
            </div>

            <div class="flex justify-end pt-2 border-t">
                <button onclick="closeProfileModal()" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-sm shadow-md transition-colors">Закрыть</button>
            </div>
        </div>
    </div>

    <!-- 8.1. Выделенное модальное окно Связи и Синхронизации (#serverSyncModal) -->
    <div id="serverSyncModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4">
            <div class="flex justify-between items-center border-b pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-blue-100 flex items-center justify-center text-xl text-blue-600 font-black shadow-inner">
                        📡
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-gray-900">Связь и синхронизация</h3>
                        <p class="text-xs text-gray-500">Подключение к серверу Работомер и офлайн-база</p>
                    </div>
                </div>
                <button onclick="closeServerSyncModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold p-1">&times;</button>
            </div>

            <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
                <!-- Карточка статуса соединения -->
                <div id="syncStatusCard" class="p-4 bg-gray-50 rounded-2xl border border-gray-200/80 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase text-gray-500 tracking-wider">Текущий статус</span>
                        <div id="syncModalStatusBadge" class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <span>В сети (Синхронизировано)</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-600 pt-1 border-t border-gray-200/60">
                        <span id="syncModalPingText">⚡ Задержка (пинг): <b class="text-gray-900">-</b></span>
                        <span id="syncModalLastSyncText">🕒 Синхр.: <b class="text-gray-900">-</b></span>
                    </div>
                    <div id="syncModalErrorBox" class="hidden p-2 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700"></div>
                </div>

                <!-- Ввод адреса сервера -->
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200/80 space-y-2.5">
                    <label class="block text-xs font-black uppercase text-gray-500 tracking-wider">Адрес сервера (URL / IP с портом)</label>
                    <div class="flex gap-2">
                        <input type="url" id="settingServerUrl" placeholder="http://192.168.100.2:7880" class="flex-1 bg-white border border-gray-300 rounded-xl p-2.5 text-xs font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        <button onclick="saveAndTestServerUrl()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-sm transition-colors flex items-center gap-1">
                            <span>💾</span> Сохранить
                        </button>
                    </div>
                </div>

                <!-- Быстрые шаблоны / Предустановленные адреса -->
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200/80 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-black uppercase text-gray-500 tracking-wider">Быстрое переключение адреса</h4>
                        <span class="text-[11px] text-gray-400">1 тап для подключения</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                        <button onclick="quickSetServerUrl('http://192.168.100.2:7880')" class="p-2.5 bg-white hover:bg-blue-50 hover:border-blue-300 border border-gray-200 rounded-xl text-left transition-all group">
                            <div class="font-bold font-sans text-[11px] text-gray-700 group-hover:text-blue-700">🏠 Роутер Wi-Fi</div>
                            <div class="text-[10px] text-gray-500 group-hover:text-blue-600">192.168.100.2:7880</div>
                        </button>
                        <button onclick="quickSetServerUrl('http://192.168.100.78:7880')" class="p-2.5 bg-white hover:bg-blue-50 hover:border-blue-300 border border-gray-200 rounded-xl text-left transition-all group">
                            <div class="font-bold font-sans text-[11px] text-gray-700 group-hover:text-blue-700">💻 Текущий ПК</div>
                            <div class="text-[10px] text-gray-500 group-hover:text-blue-600">192.168.100.78:7880</div>
                        </button>
                        <button onclick="quickSetServerUrl('http://10.177.61.62:7880')" class="p-2.5 bg-white hover:bg-amber-50 hover:border-amber-300 border border-gray-200 rounded-xl text-left transition-all group">
                            <div class="font-bold font-sans text-[11px] text-gray-700 group-hover:text-amber-700">📱 Хотспот 1</div>
                            <div class="text-[10px] text-gray-500 group-hover:text-amber-600">10.177.61.62:7880</div>
                        </button>
                        <button onclick="quickSetServerUrl('http://10.129.176.1:7880')" class="p-2.5 bg-white hover:bg-amber-50 hover:border-amber-300 border border-gray-200 rounded-xl text-left transition-all group">
                            <div class="font-bold font-sans text-[11px] text-gray-700 group-hover:text-amber-700">📱 Хотспот 2</div>
                            <div class="text-[10px] text-gray-500 group-hover:text-amber-600">10.129.176.1:7880</div>
                        </button>
                        <button onclick="quickSetServerUrl('http://10.177.61.1:7880')" class="p-2.5 bg-white hover:bg-amber-50 hover:border-amber-300 border border-gray-200 rounded-xl text-left transition-all group">
                            <div class="font-bold font-sans text-[11px] text-gray-700 group-hover:text-amber-700">📱 Хотспот 3 (Шлюз)</div>
                            <div class="text-[10px] text-gray-500 group-hover:text-amber-600">10.177.61.1:7880</div>
                        </button>
                        <button onclick="quickSetServerUrl('http://10.0.2.2:7880')" class="p-2.5 bg-white hover:bg-purple-50 hover:border-purple-300 border border-gray-200 rounded-xl text-left transition-all group">
                            <div class="font-bold font-sans text-[11px] text-gray-700 group-hover:text-purple-700">🤖 Эмулятор Android</div>
                            <div class="text-[10px] text-gray-500 group-hover:text-purple-600">10.0.2.2:7880</div>
                        </button>
                    </div>
                </div>

                <!-- Блок Автопоиска в подсети -->
                <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-200/80 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-xs font-black uppercase text-blue-900 tracking-wider">Автопоиск сервера в сети</h4>
                            <p class="text-[11px] text-blue-700">WebRTC определение подсети и сканирование 1..254</p>
                        </div>
                        <button id="btnStartScanSubnet" onclick="startSubnetAutoDiscovery()" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-md transition-all flex items-center gap-1.5">
                            <span>🔍</span> Начать поиск
                        </button>
                    </div>
                    
                    <div id="scanProgressContainer" class="hidden space-y-1.5 pt-1">
                        <div class="flex justify-between text-[11px] text-indigo-900 font-semibold">
                            <span id="scanProgressText">Сканирование...</span>
                            <span id="scanProgressPercent">0%</span>
                        </div>
                        <div class="w-full bg-indigo-200/60 rounded-full h-2 overflow-hidden">
                            <div id="scanProgressBar" class="bg-indigo-600 h-2 rounded-full transition-all duration-300" style="width: 0%;"></div>
                        </div>
                    </div>
                </div>

                <!-- Блок управления синхронизацией -->
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200/80 space-y-2.5">
                    <h4 class="text-xs font-black uppercase text-gray-500 tracking-wider">Действия синхронизации</h4>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <button onclick="triggerManualSync()" class="flex-1 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl text-xs shadow-md transition-colors flex items-center justify-center gap-1.5">
                            <span>🔄</span> Синхронизировать (Push & Pull)
                        </button>
                        <button onclick="triggerBootstrapSync()" class="py-2.5 px-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold rounded-xl text-xs transition-colors flex items-center justify-center gap-1.5">
                            <span>📥</span> Полная выгрузка базы
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-2 border-t">
                <button onclick="closeServerSyncModal()" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-sm shadow-md transition-colors">Закрыть</button>
            </div>
        </div>
    </div>

    <!-- 9. Модальное окно проверки и загрузки обновлений (#updateModal) -->'''

html = re.sub(old_modal_pattern, new_modals, html)

with open('/home/alexey/www/time-android/www/index.html', 'w', encoding='utf-8') as f:
    f.write(html)

print("Updated index.html successfully!")
