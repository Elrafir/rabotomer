<!-- Полноэкранный просмотрщик документов и изображений -->
<!-- Открывается по клику на файл из списка прикреплённых/внешних файлов -->
<!-- JS управляет содержимым через #docViewerContent и заголовком через #docViewerTitle -->
<div id="docViewerModal" class="hidden fixed inset-0 z-[99999] bg-black bg-opacity-90 flex flex-col js-modal-overlay" data-modal="docViewerModal">

    <!-- Шапка просмотрщика: название файла и кнопка закрытия -->
    <div class="flex justify-between items-center p-4 flex-shrink-0">
        <!-- Название просматриваемого файла -->
        <h3 id="docViewerTitle" class="text-white font-bold text-lg truncate pr-4">Просмотр файла</h3>
        <!-- Кнопка закрытия (крестик) -->
        <button type="button" class="js-modal-close text-white hover:text-gray-300 transition-colors flex-shrink-0" data-modal="docViewerModal">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <!-- Контейнер контента (изображение, текст или заглушка) -->
    <div id="docViewerContent" class="flex-grow flex flex-col p-4 overflow-auto min-h-0">
        <!-- Содержимое подставляется динамически через JS -->
    </div>

    <!-- Спиннер загрузки (показывается при AJAX-запросе текстового превью) -->
    <div id="docViewerSpinner" class="hidden absolute inset-0 flex items-center justify-center bg-black bg-opacity-50">
        <svg class="w-12 h-12 text-white animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>
</div>
