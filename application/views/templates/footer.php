<footer>
    <!-- Подвальные ссылки или копирайты -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-center text-gray-500">
        <p class="text-gray-500 mb-0">&copy; 2026 Тайм-трекер. Все права защищены.</p>
        <a href="<?= site_url('MobileApp') ?>" class="inline-block mt-2 text-blue-600 hover:text-blue-800 hover:underline transition-colors font-medium">Установить приложения</a>
    </div>
</footer>

<!-- Динамический вывод дополнительных JS файлов (если они переданы из контроллера) -->
<?php if (isset($custom_js) && is_array($custom_js)): ?>
    <?php foreach ($custom_js as $js): ?>
        <script src="<?php echo base_url($js) . '?v=' . time(); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</html>
