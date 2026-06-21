/**
 * Модуль управления модальными окнами раздела «Заказчики».
 * Все функции открытия/закрытия модалок и делегированные обработчики.
 * Доступен через глобальный объект window.CustomersModals.
 */

// Создаём глобальный объект управления модалками
window.CustomersModals = {

    /**
     * Открывает модалку добавления нового заказчика.
     * Просто снимает класс hidden с оверлея.
     */
    openAddCustomerModal: function() {
        // Показываем модальное окно добавления заказчика
        $('#addCustomerModal').removeClass('hidden');
    },

    /**
     * Закрывает модалку добавления заказчика.
     * Добавляет класс hidden для скрытия оверлея.
     */
    closeAddCustomerModal: function() {
        // Скрываем модальное окно добавления заказчика
        $('#addCustomerModal').addClass('hidden');
    },

    /**
     * Открывает модалку редактирования заказчика с предзаполнением полей.
     * Данные берутся из data-атрибутов кнопки и скрытого div с заметками.
     * @param {number|string} id — ID заказчика
     * @param {string} name — Имя заказчика
     * @param {string} notes — Заметки о заказчике
     * @param {string} defaultPrice — Цена по умолчанию
     * @param {string} defaultPrepayment — Предоплата по умолчанию
     * @param {string} defaultPaymentType — Тип оплаты (hourly/fixed)
     */
    openEditCustomerModal: function(id, name, notes, defaultPrice, defaultPrepayment, defaultPaymentType) {
        // Заполняем скрытое поле ID заказчика
        $('#editCustomerId').val(id);
        // Заполняем поле имени
        $('#editCustomerName').val(name);
        // Заполняем поле заметок
        $('#editCustomerNotes').val(notes);
        // Заполняем поле цены по умолчанию (с фоллбэком на 0.00)
        $('#editCustomerDefaultPrice').val(defaultPrice || '0.00');
        // Заполняем поле предоплаты по умолчанию
        $('#editCustomerDefaultPrepayment').val(defaultPrepayment || '0.00');
        // Выбираем тип оплаты в селекте
        $('#editCustomerDefaultPaymentType').val(defaultPaymentType || 'hourly');
        // Показываем модальное окно
        $('#editCustomerModal').removeClass('hidden');
    },

    /**
     * Закрывает модалку редактирования заказчика.
     */
    closeEditCustomerModal: function() {
        // Скрываем модальное окно редактирования заказчика
        $('#editCustomerModal').addClass('hidden');
    },

    /**
     * Открывает модалку просмотра информации о заказчике.
     */
    openCustomerInfoModal: function() {
        // Показываем модальное окно информации о заказчике
        $('#customerInfoModal').removeClass('hidden');
    },

    /**
     * Закрывает модалку просмотра информации о заказчике.
     */
    closeCustomerInfoModal: function() {
        // Скрываем модальное окно информации
        $('#customerInfoModal').addClass('hidden');
    },

    /**
     * УНИВЕРСАЛЬНАЯ функция открытия модалки ТЗ в одном из двух режимов.
     * @param {string} mode — Режим: 'add' (создание) или 'edit' (редактирование)
     * @param {Object} [data] — Данные для предзаполнения (при mode='edit'):
     *   {id, title, content, price, prepayment, paymentType, linkedTaskIds, filesDir}
     */
    openSpecFormModal: function(mode, data) {
        // Получаем контейнер модалки
        var modal = $('#specFormModal');
        // Получаем форму
        var form = $('#specForm');

        // Устанавливаем текущий режим в data-атрибут
        modal.attr('data-mode', mode);

        if (mode === 'edit') {
            // --- РЕЖИМ РЕДАКТИРОВАНИЯ ---

            // Устанавливаем заголовок модалки
            $('#specFormTitle').text('Редактирование ТЗ');
            // Устанавливаем action формы на URL редактирования
            form.attr('action', modal.data('action-edit'));
            // Заполняем скрытое поле ID ТЗ
            $('#specFormSpecId').val(data.id);
            // Заполняем название ТЗ
            $('#specFormTitleInput').val(data.title);
            // Заполняем путь к директории файлов
            $('#specFormFilesDir').val(data.filesDir || '');
            // Заполняем цену
            $('#specFormPrice').val(data.price || '0.00');
            // Заполняем предоплату
            $('#specFormPrepayment').val(data.prepayment || '0.00');
            // Выбираем тип оплаты
            $('#specFormPaymentType').val(data.paymentType || 'hourly');

            // Снимаем отметки со всех чекбоксов привязки задач
            $('.spec-task-checkbox').prop('checked', false);
            // Отмечаем привязанные задачи
            if (data.linkedTaskIds && Array.isArray(data.linkedTaskIds)) {
                // Перебираем ID привязанных задач и ставим чекбоксы
                data.linkedTaskIds.forEach(function(taskId) {
                    $('.spec-task-checkbox[value="' + taskId + '"]').prop('checked', true);
                });
            }

            // Загружаем контент ТЗ в Quill-редактор
            if (window.specQuill) {
                // Если Quill доступен — вставляем HTML-контент
                window.specQuill.clipboard.dangerouslyPasteHTML(data.content || '');
            } else {
                // Фоллбэк: вставляем в textarea
                $('#spec-editor-fallback').val(data.content || '');
            }

        } else {
            // --- РЕЖИМ СОЗДАНИЯ ---

            // Устанавливаем заголовок для нового ТЗ
            $('#specFormTitle').text('Новое ТЗ');
            // Устанавливаем action формы на URL добавления
            form.attr('action', modal.data('action-add'));
            // Очищаем поле ID ТЗ (не нужно при создании)
            $('#specFormSpecId').val('');
            // Сбрасываем форму — очищаем все текстовые поля и чекбоксы
            form[0].reset();

            // Очищаем Quill-редактор
            if (window.specQuill) {
                // Сбрасываем содержимое Quill
                window.specQuill.setContents([]);
            } else {
                // Фоллбэк: очищаем textarea
                $('#spec-editor-fallback').val('');
            }
        }

        // Показываем модальное окно
        modal.removeClass('hidden');
    },

    /**
     * Закрывает модалку создания/редактирования ТЗ.
     */
    closeSpecFormModal: function() {
        // Скрываем модальное окно ТЗ
        $('#specFormModal').addClass('hidden');
    },

    /**
     * Закрывает полноэкранный просмотрщик документов.
     * Очищает контент и скрывает спиннер.
     */
    closeDocViewerModal: function() {
        // Скрываем модальное окно просмотрщика
        $('#docViewerModal').addClass('hidden');
        // Очищаем содержимое просмотрщика
        $('#docViewerContent').empty();
        // Скрываем спиннер загрузки
        $('#docViewerSpinner').addClass('hidden');
    },

    /**
     * Инициализирует делегированные обработчики для кнопок модалок.
     * Используется вместо inline onclick для чистоты HTML.
     */
    initEditModalsHandlers: function() {
        // --- Делегированный обработчик открытия модалки добавления заказчика ---
        $(document).off('click', '[data-action="open-add-customer"]').on('click', '[data-action="open-add-customer"]', function(e) {
            // Предотвращаем стандартное поведение кнопки
            e.preventDefault();
            // Открываем модалку добавления
            window.CustomersModals.openAddCustomerModal();
        });

        // --- Делегированный обработчик открытия модалки информации ---
        $(document).off('click', '[data-action="open-customer-info"]').on('click', '[data-action="open-customer-info"]', function(e) {
            // Предотвращаем стандартное поведение кнопки
            e.preventDefault();
            // Открываем модалку информации
            window.CustomersModals.openCustomerInfoModal();
        });

        // --- Делегированный обработчик открытия модалки добавления ТЗ ---
        $(document).off('click', '[data-action="open-add-spec"]').on('click', '[data-action="open-add-spec"]', function(e) {
            // Предотвращаем стандартное поведение кнопки
            e.preventDefault();
            // Открываем модалку ТЗ в режиме добавления
            window.CustomersModals.openSpecFormModal('add');
        });

        // --- Делегированный обработчик редактирования заказчика ---
        $(document).off('click', '#editCustomerBtn').on('click', '#editCustomerBtn', function(e) {
            // Предотвращаем стандартное поведение кнопки
            e.preventDefault();
            // Получаем jQuery-обёртку кнопки
            var btn = $(this);
            // Считываем ID заказчика из data-атрибута
            var id = btn.data('id');
            // Считываем имя заказчика
            var name = btn.data('name');
            // Считываем цену по умолчанию
            var price = btn.data('price');
            // Считываем предоплату по умолчанию
            var prepayment = btn.data('prepayment');
            // Считываем тип оплаты
            var paymentType = btn.data('payment-type');
            // Считываем многострочные заметки из скрытого div
            var notes = $('#customer-notes-data-' + id).text() || '';
            // Открываем модалку редактирования с данными
            window.CustomersModals.openEditCustomerModal(id, name, notes, price, prepayment, paymentType);
        });

        // --- Делегированный обработчик редактирования ТЗ (спецификации) ---
        $(document).off('click', '.edit-spec-btn').on('click', '.edit-spec-btn', function(e) {
            // Предотвращаем стандартное поведение кнопки
            e.preventDefault();
            // Получаем jQuery-обёртку кнопки
            var btn = $(this);
            // Считываем ID ТЗ
            var id = btn.data('id');
            // Считываем название ТЗ
            var title = btn.data('title');
            // Считываем цену ТЗ
            var price = btn.data('price');
            // Считываем предоплату ТЗ
            var prepayment = btn.data('prepayment');
            // Считываем тип оплаты ТЗ
            var paymentType = btn.data('payment-type');
            // Считываем путь к директории файлов
            var filesDir = btn.data('files-dir');
            // jQuery автоматически парсит JSON из data-атрибута
            var linkedTaskIds = btn.data('linked-tasks') || [];
            // Считываем HTML-контент ТЗ из скрытого div.
            // ВАЖНО: используем .text(), а не .html() — контент в div экранирован через
            // htmlspecialchars(), и .text() автоматически декодирует &lt; → <, &amp; → & и т.д.
            var content = $('#spec-content-data-' + id).text() || '';
            // Открываем модалку ТЗ в режиме редактирования
            window.CustomersModals.openSpecFormModal('edit', {
                id: id,
                title: title,
                content: content,
                price: price,
                prepayment: prepayment,
                paymentType: paymentType,
                linkedTaskIds: linkedTaskIds,
                filesDir: filesDir
            });
        });

        // --- Делегированный обработчик закрытия модалок через оверлей ---
        $(document).off('click', '.js-modal-overlay').on('click', '.js-modal-overlay', function(e) {
            // Проверяем, что клик был по самому оверлею, а не по контенту модалки
            if ($(e.target).hasClass('js-modal-overlay')) {
                // Получаем имя модалки из data-атрибута
                var modalName = $(this).data('modal');
                // Скрываем модалку
                $('#' + modalName).addClass('hidden');
                // Если это просмотрщик документов — дополнительно очищаем контент
                if (modalName === 'docViewerModal') {
                    $('#docViewerContent').empty();
                    $('#docViewerSpinner').addClass('hidden');
                }
            }
        });

        // --- Делегированный обработчик кнопок закрытия модалок (крестики) ---
        $(document).off('click', '.js-modal-close').on('click', '.js-modal-close', function(e) {
            // Предотвращаем стандартное поведение кнопки
            e.preventDefault();
            // Предотвращаем всплытие (чтобы не сработал обработчик оверлея)
            e.stopPropagation();
            // Получаем имя модалки из data-атрибута
            var modalName = $(this).data('modal');
            // Скрываем модалку
            $('#' + modalName).addClass('hidden');
            // Если это просмотрщик документов — дополнительно очищаем контент
            if (modalName === 'docViewerModal') {
                $('#docViewerContent').empty();
                $('#docViewerSpinner').addClass('hidden');
            }
        });

        // --- Делегированный обработчик ссылок с подтверждением удаления ---
        $(document).off('click', '.js-confirm-delete').on('click', '.js-confirm-delete', function(e) {
            // Считываем текст подтверждения из data-атрибута
            var message = $(this).data('confirm-message') || 'Вы уверены?';
            // Если пользователь не подтвердил — отменяем переход
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    }
};

// Обратная совместимость: глобальные функции для старого кода
// Позволяют работать существующим вызовам вида openAddCustomerModal()
window.openAddCustomerModal = window.CustomersModals.openAddCustomerModal;
window.closeAddCustomerModal = window.CustomersModals.closeAddCustomerModal;
window.openEditCustomerModal = window.CustomersModals.openEditCustomerModal;
window.closeEditCustomerModal = window.CustomersModals.closeEditCustomerModal;
window.openCustomerInfoModal = window.CustomersModals.openCustomerInfoModal;
window.closeCustomerInfoModal = window.CustomersModals.closeCustomerInfoModal;
window.closeDocViewerModal = window.CustomersModals.closeDocViewerModal;
window.initEditModalsHandlers = window.CustomersModals.initEditModalsHandlers;
