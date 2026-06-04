<footer>
    <!-- Подвальные ссылки или копирайты -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-center text-gray-500">
        &copy; <?php echo date('Y'); ?> Тайм-трекер. Все права защищены.
    </div>
</footer>

<!-- Динамический вывод дополнительных JS файлов (если они переданы из контроллера) -->
<?php if (isset($custom_js) && is_array($custom_js)): ?>
    <?php foreach ($custom_js as $js): ?>
        <script src="<?php echo base_url($js); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</html>
