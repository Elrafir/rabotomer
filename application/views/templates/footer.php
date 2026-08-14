<footer class="mt-auto py-6 border-t border-gray-200/40 dark:border-gray-800/40">
    <!-- Подвальные ссылки и панель действий -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-500">
        <p class="text-gray-500 mb-0">&copy; 2026 Тайм-трекер. Все права защищены.</p>
        
        <div class="flex items-center gap-3 flex-wrap justify-center sm:justify-end">
            <a href="<?= site_url('MobileApp') ?>" class="inline-flex items-center gap-1.5 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition-colors font-medium">
                <span>📱</span> Установить приложения
            </a>
            <span class="text-gray-300 dark:text-gray-700">|</span>
            <button onclick="window.location.reload()" class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors cursor-pointer bg-transparent border-0 p-0 text-xs" title="Перезагрузить страницу (F5)">
                <span>🔄</span> Обновить страницу
            </button>
            <span class="text-gray-300 dark:text-gray-700">|</span>
            <button onclick="if(window.goToSetupScreen){window.goToSetupScreen();}else{window.location.href='<?= site_url('MobileApp/reset_setup'); ?>';}" class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors cursor-pointer bg-transparent border-0 p-0 text-xs" title="Выйти на стартовую страницу выбора сервера">
                <span>🌐</span> Выбор сервера
            </button>
        </div>
    </div>
</footer>

<!-- Динамический вывод дополнительных JS файлов (если они переданы из контроллера) -->
<?php if (isset($custom_js) && is_array($custom_js)): ?>
    <?php foreach ($custom_js as $js): ?>
        <script src="<?php echo base_url($js) . '?v=' . time(); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</html>
