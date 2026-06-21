/**
 * Модуль утилит для раздела «Заказчики».
 * Содержит вспомогательные функции: парсинг JSON, определение типов файлов.
 * Все функции доступны через глобальный объект window.CustomersUtils.
 */

// Создаём глобальный объект утилит
window.CustomersUtils = {

    /**
     * Безопасно парсит JSON-ответ сервера, очищая от возможных PHP-нотисов/предупреждений.
     * PHP-ошибки могут добавлять HTML перед JSON-ответом, что ломает JSON.parse().
     * @param {string} response — сырой текстовый ответ сервера
     * @returns {Object} — распарсенный JSON-объект
     * @throws {Error} — если JSON не найден в ответе
     */
    safeParseJson: function(response) {
        // Ищем начало JSON-объекта (первую открывающую фигурную скобку)
        var jsonStart = response.indexOf('{');
        // Ищем конец JSON-объекта (последнюю закрывающую фигурную скобку)
        var jsonEnd = response.lastIndexOf('}');
        // Если не нашли ни начала, ни конца — ответ битый
        if (jsonStart === -1 || jsonEnd === -1) {
            throw new Error('Неверный формат ответа сервера');
        }
        // Вырезаем чистый JSON из ответа
        var cleanJson = response.substring(jsonStart, jsonEnd + 1);
        // Парсим и возвращаем объект
        return JSON.parse(cleanJson);
    },

    /**
     * Проверяет, является ли файл изображением по расширению.
     * @param {string} ext — расширение файла (без точки, в нижнем регистре)
     * @returns {boolean} — true если файл — изображение
     */
    isImageFile: function(ext) {
        // Список поддерживаемых расширений изображений
        var imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        // Проверяем вхождение в массив
        return imageExts.indexOf(ext) !== -1;
    },

    /**
     * Проверяет, является ли файл текстовым по расширению.
     * @param {string} ext — расширение файла (без точки, в нижнем регистре)
     * @returns {boolean} — true если файл — текстовый
     */
    isTextFile: function(ext) {
        // Список поддерживаемых текстовых расширений
        var textExts = ['txt', 'log', 'sql', 'json', 'xml', 'csv', 'md', 'ini', 'cfg', 'yaml', 'yml', 'html', 'js', 'css'];
        // Проверяем вхождение в массив
        return textExts.indexOf(ext) !== -1;
    },

    /**
     * Извлекает расширение из имени файла.
     * @param {string} filename — имя файла (например, "document.pdf")
     * @returns {string} — расширение в нижнем регистре (например, "pdf")
     */
    getFileExt: function(filename) {
        // Разбиваем имя по точке и берём последний элемент
        return filename.split('.').pop().toLowerCase();
    }
};
