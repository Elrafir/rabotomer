<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Тайм-трекер'; ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo base_url('assets/img/clock-icon.svg'); ?>">
    
    <!-- PWA Settings -->
    <link rel="manifest" href="<?php echo base_url('manifest.json'); ?>">
    <meta name="theme-color" content="#2563eb">
    <link rel="apple-touch-icon" href="<?php echo base_url('assets/img/clock-icon.svg'); ?>">

    <!-- Локальное подключение Tailwind CSS -->
    <link href="<?php echo base_url('assets/css/tailwind.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo base_url('assets/css/main.css?v='.time()); ?>" rel="stylesheet">
    <!-- Локальное подключение jQuery -->
    <script src="<?php echo base_url('assets/js/jquery.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/js/main.js?v='.time()); ?>"></script>
    <script src="<?php echo base_url('assets/js/timer.js?v='.time()); ?>"></script>
    <script src="<?php echo base_url('assets/js/offline-sync.js?v='.time()); ?>"></script>
    <?php 
        $ci =& get_instance();
        $ci->load->config('app_version', TRUE, TRUE);
        $v_code = (int)($ci->config->item('app_version_code', 'app_version') ?? 8);
        $v_name = $ci->config->item('app_version', 'app_version') ?? '1.0.7';
    ?>
    <script>
        window.CURRENT_APP_VERSION_CODE = <?= $v_code ?>;
        window.CURRENT_APP_VERSION = "<?= htmlspecialchars($v_name) ?>";
    </script>
    <script src="<?php echo base_url('assets/js/mobile-heartbeat.js?v='.time()); ?>"></script>
    
    <!-- Динамический вывод дополнительных CSS файлов (если они переданы из контроллера) -->
    <?php if (isset($custom_css) && is_array($custom_css)): ?>
        <?php foreach ($custom_css as $css): ?>
            <link href="<?php echo base_url($css) . '?v=' . time(); ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
