/**
 * Точка входа модуля «Заказчики».
 * Координирует инициализацию всех подмодулей.
 * Защита от повторной инициализации при SPA-переходах.
 */

// Считываем activeCustomerId из data-атрибута корневого div (вместо inline script)
(function() {
    // Получаем корневой контейнер раздела
    var root = document.getElementById('customers-root');
    // Если контейнер найден — считываем data-атрибут
    if (root) {
        // Преобразуем строку в число или null
        var rawId = root.getAttribute('data-active-customer-id');
        // Устанавливаем глобальную переменную для совместимости с другими модулями
        window.activeCustomerId = rawId ? parseInt(rawId, 10) : null;
    }
})();

// Проверяем, был ли модуль уже загружен ранее (защита от повторной инициализации при AJAX-навигации)
if (window.loadedCustomersModule) {
    // --- ПОВТОРНАЯ ЗАГРУЗКА (SPA-переход) ---

    // Переинициализируем Quill-редактор для нового HTML
    if (typeof window.initQuillEditor === 'function') {
        window.initQuillEditor();
    }

    // Переинициализируем обработчик сабмита формы ТЗ
    if (typeof window.initSpecFormSubmit === 'function') {
        window.initSpecFormSubmit();
    }

    // Переинициализируем бесконечный скролл заказчиков
    if (typeof window.initInfiniteScrollCustomers === 'function') {
        window.initInfiniteScrollCustomers();
    }

    // Переинициализируем бесконечный скролл задач
    if (typeof window.initInfiniteScrollTasks === 'function') {
        window.initInfiniteScrollTasks();
    }

    // Переинициализируем предпросмотр файлов
    if (typeof window.initFilePreviews === 'function') {
        window.initFilePreviews();
    }

    // Переинициализируем обработчики модалок
    if (typeof window.initEditModalsHandlers === 'function') {
        window.initEditModalsHandlers();
    }

    // Переинициализируем обработчики файловых операций
    if (typeof window.initFileUploadHandlers === 'function') {
        window.initFileUploadHandlers();
    }

    // Переинициализируем переключатель закрытых задач и сворачивание
    if (typeof window.initClosedTasksToggle === 'function') {
        window.initClosedTasksToggle();
    }

    // Переинициализируем аккордеоны ТЗ
    if (typeof window.initSpecAccordions === 'function') {
        window.initSpecAccordions();
    }

} else {
    // --- ПЕРВАЯ ЗАГРУЗКА ---

    // Устанавливаем флаг, что модуль уже загружен
    window.loadedCustomersModule = true;

    // Глобальная переменная Quill-редактора (единственный экземпляр)
    window.specQuill = null;

    // Инициализируем Quill-редактор
    window.initQuillEditor();

    // Инициализируем обработчик сабмита формы ТЗ
    window.initSpecFormSubmit();

    // Инициализируем бесконечный скролл заказчиков
    window.initInfiniteScrollCustomers();

    // Инициализируем бесконечный скролл задач
    window.initInfiniteScrollTasks();

    // Инициализируем переключатель закрытых задач и сворачивание подзадач
    window.initClosedTasksToggle();

    // Инициализируем аккордеоны ТЗ
    window.initSpecAccordions();

    // Инициализируем предпросмотр файлов (поповер + полноэкранный просмотр)
    window.initFilePreviews();

    // Инициализируем обработчики модалок (открытие, закрытие, редактирование)
    window.initEditModalsHandlers();

    // Инициализируем обработчики файловых операций (drag-drop, загрузка, удаление)
    window.initFileUploadHandlers();
}
