// =========================================================================
// ГЛОБАЛЬНЫЙ КОРНЕВОЙ СКРИПТ УПРАВЛЕНИЯ ПРИЛОЖЕНИЕМ (MAIN.JS)
// Содержит: SPA-навигацию, глобальное создание проектов, кастомные темы
// =========================================================================

// --- ГЛОБАЛЬНОЕ МОДАЛЬНОЕ ОКНО СОЗДАНИЯ ПРОЕКТА ---

/**
 * Открывает глобальное модальное окно добавления нового проекта
 */
window.openGlobalAddModal = function() {
    $('#globalAddModal').removeClass('hidden');
    // Устанавливаем фокус ввода на название задачи с небольшой задержкой для плавности
    setTimeout(() => $('#globalAddModal input[name="title"]').focus(), 100);
};

/**
 * Закрывает глобальное модальное окно добавления
 */
window.closeGlobalAddModal = function() {
    $('#globalAddModal').addClass('hidden');
};

/**
 * Управляет видимостью полей нового клиента в зависимости от выбора в глобальном селекте
 * @param {HTMLSelectElement} selectElem - Выбранный элемент списка клиентов
 */
window.updateRateGlobal = function(selectElem) {
    const val = $(selectElem).val();
    
    // Если выбрано создание нового клиента, показываем текстовые поля ввода имени
    if (val === 'new') {
        $('#newCustomerFields').removeClass('hidden');
        $('#globalAddIsFixed').parent().find('.customer-select').removeClass('flex-1').addClass('w-full sm:w-1/3');
    } else {
        // Иначе скрываем эти поля
        $('#newCustomerFields').addClass('hidden');
        $('#globalAddIsFixed').parent().find('.customer-select').addClass('flex-1').removeClass('w-full sm:w-1/3');
    }
};

// --- ГЛОБАЛЬНЫЙ ОБРАБОТЧИК ВЫБОРА КЛИЕНТА ДЛЯ ПОДГРУЗКИ ТЗ ---
$(document).on('change', '.customer-select', function() {
    var customerId = $(this).val();
    var form = $(this).closest('form, #editTaskModal');
    var specSelect = form.find('.spec-select');
    var specContainer = form.find('.spec-container, #globalAddSpecContainer, #editTaskSpecContainer');
    
    // Если клиент не выбран или выбран новый клиент, очищаем список ТЗ и скрываем блок
    if (!customerId || customerId === 'new') {
        specSelect.html('<option value="">Связать с ТЗ...</option>');
        specContainer.addClass('hidden');
        return;
    }
    
    // Выполняем AJAX-запрос для получения списка ТЗ выбранного клиента
    $.getJSON(window.location.origin + '/index.php/customers/get_specs_ajax/' + customerId, function(res) {
        if (res.status === 'success') {
            var html = '<option value="">Связать с ТЗ...</option>';
            if (res.data && res.data.length > 0) {
                res.data.forEach(function(spec) {
                    html += '<option value="' + spec.id + '">' + spec.title + '</option>';
                });
                specSelect.html(html);
                specContainer.removeClass('hidden');
                
                // Если было сохранено временно выбранное ТЗ (при редактировании задачи)
                var savedSpecId = specSelect.data('pending-select');
                if (savedSpecId) {
                    specSelect.val(savedSpecId);
                    specSelect.removeData('pending-select');
                }
            } else {
                specSelect.html('<option value="">Нет созданных ТЗ</option>');
                specContainer.addClass('hidden');
            }
        } else {
            specSelect.html('<option value="">Ошибка загрузки ТЗ</option>');
            specContainer.addClass('hidden');
        }
    });
});

// --- ГЛОБАЛЬНАЯ НАВИГАЦИЯ БЕЗ ПЕРЕЗАГРУЗКИ (AJAX SPA) ---

$(document).ready(function() {
    // Перехватываем клики по всем внутренним ссылкам (a)
    $(document).on('click', 'a', function(e) {
        const href = $(this).attr('href');
        
        // Пропускаем невалидные ссылки, якоря и вызовы JS
        if (!href || href === '#' || href.startsWith('javascript:')) return;
        
        // Пропускаем ссылки, которые должны открыться в новой вкладке
        if ($(this).attr('target') === '_blank') return;
        
        // Пропускаем админку и авторизацию, чтобы они загружались классически
        if (href.indexOf('/admin') !== -1 || href.indexOf('/auth') !== -1) return;
        
        // Проверяем, что ссылка ведет на наш домен
        if (href.startsWith('http') && !href.startsWith(window.location.origin)) return;

        // Отменяем стандартный переход и загружаем страницу по AJAX
        e.preventDefault();
        window.loadAjaxPage(href);
    });

    // Обработка системных кнопок браузера Назад/Вперед
    $(window).on('popstate', function(e) {
        window.loadAjaxPage(window.location.href, false);
    });
});

/**
 * Загружает HTML-контент страницы через AJAX и монтирует его в #main-content
 * @param {string} url - Ссылка на загружаемую страницу
 * @param {boolean} push - Флаг необходимости сохранения ссылки в истории браузера
 */
window.loadAjaxPage = function(url, push = true) {
    // Визуальный эффект загрузки (затемнение контента)
    $('#main-content').css('opacity', '0.5');
    
    $.ajax({
        url: url,
        type: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }, // Метка для CodeIgniter, что это AJAX-запрос
        success: function(html) {
            // Монтируем новый контент страницы
            $('#main-content').html(html);
            $('#main-content').css('opacity', '1');
            
            // Записываем новый URL в адресную строку браузера
            if (push) {
                window.history.pushState(null, '', url);
            }
            
            // Корректируем подсветку активного пункта меню
            updateActiveMenu(url);
            
            // Прокручиваем страницу на самый верх
            window.scrollTo(0, 0);
        },
        error: function() {
            // Фолбэк: при ошибке сети переходим по старинке с перезагрузкой
            window.location.href = url;
        }
    });
};

/**
 * Обновляет CSS-классы в шапке навигации для выделения активной ссылки
 * @param {string} url - Текущий URL адрес страницы
 */
function updateActiveMenu(url) {
    // Снимаем класс подсветки со всех элементов меню
    $('nav a.transition-all').removeClass('opacity-100 nav-cloud-active').addClass('opacity-70 hover:opacity-100');
    
    // Проходимся циклом по всем ссылкам и ищем совпадение
    $('nav a.transition-all').each(function() {
        const linkHref = $(this).attr('href');
        if (!linkHref) return;
        
        // Подсветка дашборда (корень сайта)
        if (url === window.location.origin + '/' || url === window.location.origin) {
            if (linkHref === window.location.origin + '/' || linkHref === window.location.origin + '/tasks') {
                $(this).removeClass('opacity-70 hover:opacity-100').addClass('opacity-100 nav-cloud-active');
                window.isDashboardPage = true;
            }
        } 
        // Подсветка остальных разделов
        else if (url.indexOf(linkHref) !== -1 && linkHref !== window.location.origin + '/') {
            $(this).removeClass('opacity-70 hover:opacity-100').addClass('opacity-100 nav-cloud-active');
            if (linkHref.indexOf('/tasks') !== -1) {
                window.isDashboardPage = true;
            } else {
                window.isDashboardPage = false;
            }
        }
    });
    
    // Обновляем состояние отображения плавающего виджета таймера
    if (typeof showTimerUI === 'function' && typeof hideTimerUI === 'function') {
        if (globalTimerInterval || isPaused) {
            showTimerUI();
        } else {
            hideTimerUI();
        }
    }
}

// --- ОТПРАВКА ФОРМ АВТОРИЗАЦИИ И ПРОФИЛЯ ---

// Обработчик отправки формы регистрации
$(document).on('submit', '#register-form', function(e) {
    e.preventDefault();
    let btn = $(this).find('button[type="submit"]');
    let spinner = btn.find('.spinner');
    let errorBox = $('#register-errors');
    
    btn.prop('disabled', true).addClass('opacity-75 cursor-not-allowed');
    spinner.removeClass('hidden');
    errorBox.addClass('hidden').html('');

    $.post($(this).attr('action'), $(this).serialize(), function(response) {
        try {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                window.loadAjaxPage('/', false);
            } else {
                errorBox.removeClass('hidden').html(res.message);
                btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
                spinner.addClass('hidden');
            }
        } catch (err) {
            errorBox.removeClass('hidden').html("Произошла системная ошибка.");
            btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
            spinner.addClass('hidden');
        }
    });
});

// Обработчик отправки формы редактирования профиля
$(document).on('submit', '#profile-form', function(e) {
    e.preventDefault();
    let btn = $(this).find('button[type="submit"]');
    let spinner = btn.find('.spinner');
    let errorBox = $('#profile-errors');
    
    btn.prop('disabled', true).addClass('opacity-75 cursor-not-allowed');
    spinner.removeClass('hidden');
    errorBox.addClass('hidden').html('');

    $.post($(this).attr('action'), $(this).serialize(), function(response) {
        try {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                window.loadAjaxPage(window.location.href, false);
            } else {
                errorBox.removeClass('hidden').html(res.message);
                btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
                spinner.addClass('hidden');
            }
        } catch (err) {
            errorBox.removeClass('hidden').html("Произошла системная ошибка.");
            btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
            spinner.addClass('hidden');
        }
    });
});

// --- ДИНАМИЧЕСКИЕ ТЕМЫ ОФОРМЛЕНИЯ И ЦВЕТ ИНТЕРФЕЙСА ---

// Выбор пресета темы оформления
$(document).on('click', '.theme-selector', function(e) {
    e.preventDefault();
    let btn = $(this);
    let themeName = btn.data('theme');
    
    // Получаем текущие значения прозрачности и тона
    let opacity = $('#theme_opacity').length ? $('#theme_opacity').val() : '1.00';
    let hue = $('#theme_hue').length ? $('#theme_hue').val() : '221';
    
    let ajaxUrl = window.location.origin + '/index.php/profile/save_theme_ajax';
    
    $.post(ajaxUrl, { theme: themeName, opacity: opacity, hue: hue }, function(response) {
        try {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                // Очищаем старые CSS-классы тем с тега body
                $('body').removeClass(function (index, className) {
                    return (className.match(/(^|\s)theme-\S+/g) || []).join(' ');
                });
                
                // Добавляем класс новой выбранной темы
                $('body').addClass(themeName);
                
                // Настраиваем видимость кастомного ползунка Hue
                if (themeName === 'theme-custom') {
                    $('#custom_hue_container').removeClass('hidden');
                    document.body.style.setProperty('--theme-h', hue);
                } else {
                    $('#custom_hue_container').addClass('hidden');
                    document.body.style.removeProperty('--theme-h');
                }
                
                // Обновляем активный кружочек с обводкой в меню выбора
                $('.theme-selector').each(function() {
                    $(this).removeClass('border-gray-800 ring-4').addClass('border-transparent hover:scale-110');
                    $(this).css('box-shadow', 'none');
                });
                
                let ringHex = '#60a5fa';
                if(themeName === 'theme-emerald') ringHex = '#34d399';
                else if(themeName === 'theme-sunset') ringHex = '#fb923c';
                else if(themeName === 'theme-berry') ringHex = '#fb7185';
                else if(themeName === 'theme-night') ringHex = '#94a3b8';
                else if(themeName === 'theme-ocean') ringHex = '#22d3ee';
                else if(themeName === 'theme-lavender') ringHex = '#c084fc';
                else if(themeName === 'theme-coffee') ringHex = '#fbbf24';
                else if(themeName === 'theme-custom') ringHex = '#a855f7';
                
                btn.removeClass('border-transparent hover:scale-110')
                   .addClass('border-gray-800 ring-4')
                   .css('box-shadow', '0 0 0 4px ' + ringHex);
                   
            } else {
                alert("Ошибка при сохранении темы: " + res.message);
            }
        } catch (err) {
            console.error("System Error: ", err);
            alert("Произошла системная ошибка при сохранении темы.");
        }
    });
});

// Обработчик ползунка прозрачности темы (движение)
$(document).on('input', '#theme_opacity', function() {
    let opacity = parseFloat($(this).val());
    // Обновляем текстовый процент
    $('#opacity_value').text(Math.round(opacity * 100) + '%');
    // Мгновенно применяем CSS-переменную к body
    document.body.style.setProperty('--theme-opacity', opacity);
});

// Сохранение прозрачности темы при отпускании ползунка
$(document).on('change', '#theme_opacity', function() {
    let opacity = $(this).val();
    let currentTheme = 'theme-default';
    let hue = $('#theme_hue').length ? $('#theme_hue').val() : '221';
    
    $('.theme-selector').each(function() {
        if ($(this).hasClass('ring-4')) {
            currentTheme = $(this).data('theme');
        }
    });
    
    let ajaxUrl = window.location.origin + '/index.php/profile/save_theme_ajax';
    $.post(ajaxUrl, { theme: currentTheme, opacity: opacity, hue: hue }, function(response) {
        try {
            let res = JSON.parse(response);
            if (res.status !== 'success') {
                alert("Ошибка сохранения прозрачности: " + res.message);
            }
        } catch (err) {
            console.error("System Error: ", err);
        }
    });
});

// Обработчик ползунка тона кастомного цвета (движение)
$(document).on('input', '#theme_hue', function() {
    let hue = $(this).val();
    $('#hue_value').html(hue + '&deg;');
    document.body.style.setProperty('--theme-h', hue);
});

// Сохранение тона кастомного цвета при отпускании ползунка
$(document).on('change', '#theme_hue', function() {
    let hue = $(this).val();
    let opacity = $('#theme_opacity').length ? $('#theme_opacity').val() : '1.00';
    let currentTheme = 'theme-custom';
    
    let ajaxUrl = window.location.origin + '/index.php/profile/save_theme_ajax';
    $.post(ajaxUrl, { theme: currentTheme, opacity: opacity, hue: hue }, function(response) {
        try {
            let res = JSON.parse(response);
            if (res.status !== 'success') {
                alert("Ошибка сохранения цвета: " + res.message);
            }
        } catch (err) {
            console.error("System Error: ", err);
        }
    });
});

/**
 * Открывает плавающее модальное окно настроек цвета интерфейса
 */
window.openThemeModal = function() {
    $('#themeSettingsModal').removeClass('hidden');
    setTimeout(() => {
        $('#themeSettingsModal').removeClass('opacity-0');
    }, 10);
};

/**
 * Закрывает модальное окно настроек цвета интерфейса
 */
window.closeThemeModal = function() {
    $('#themeSettingsModal').addClass('opacity-0');
    setTimeout(() => {
        $('#themeSettingsModal').addClass('hidden');
    }, 200);
};

// Drag and drop перемещение для модального окна тем
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('themeSettingsModal');
    const header = document.getElementById('themeSettingsModalHeader');
    
    if (!modal || !header) return;

    let isDragging = false;
    let currentX;
    let currentY;
    let initialX;
    let initialY;
    let xOffset = 0;
    let yOffset = 0;

    header.addEventListener('mousedown', dragStart);
    document.addEventListener('mousemove', drag);
    document.addEventListener('mouseup', dragEnd);

    header.addEventListener('touchstart', dragStart, {passive: false});
    document.addEventListener('touchmove', drag, {passive: false});
    document.addEventListener('touchend', dragEnd);

    function dragStart(e) {
        if (e.type === "touchstart") {
            initialX = e.touches[0].clientX - xOffset;
            initialY = e.touches[0].clientY - yOffset;
        } else {
            initialX = e.clientX - xOffset;
            initialY = e.clientY - yOffset;
        }
        
        if (e.target.closest('button')) return;
        
        isDragging = true;
        modal.classList.add('dragging-theme-modal');
    }

    function drag(e) {
        if (isDragging) {
            if (e.type === "touchmove") {
                currentX = e.touches[0].clientX - initialX;
                currentY = e.touches[0].clientY - initialY;
            } else {
                currentX = e.clientX - initialX;
                currentY = e.clientY - initialY;
            }

            xOffset = currentX;
            yOffset = currentY;

            setTranslate(currentX, currentY, modal);
        }
    }

    function setTranslate(xPos, yPos, el) {
        el.style.transform = `translate3d(${xPos}px, ${yPos}px, 0)`;
    }

    function dragEnd(e) {
        initialX = currentX;
        initialY = currentY;
        isDragging = false;
        modal.classList.remove('dragging-theme-modal');
    }
});

// Регистрация Service Worker для PWA (Офлайн-режим)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        // Определяем путь к sw.js (в корне сайта)
        const swPath = window.location.pathname.startsWith('/time/') ? '/time/sw.js' : '/sw.js';
        navigator.serviceWorker.register(swPath).then((registration) => {
            console.log('ServiceWorker registration successful with scope: ', registration.scope);
        }).catch((err) => {
            console.log('ServiceWorker registration failed: ', err);
        });
    });
}
