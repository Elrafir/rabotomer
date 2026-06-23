<?php
/**
 * Шаблон-обёртка контента: 3 колонки (лево, центр, право).
 * Левый aside сворачивается до иконок через CSS media query (< 1600px).
 */
?>

<?php if (!empty($left_sidebar_view)): ?>
<aside id="left-sidebar" class="left-sidebar flex-shrink-0">
    <?php $this->load->view($left_sidebar_view, $data ?? []); ?>
</aside>
<?php endif; ?>

<div class="flex-grow min-w-0">
    <?php if (isset($inner_view)): ?>
        <?php if(isset($active_session)) $data['active_session'] = $active_session; ?>
        <?php $this->load->view('templates/flash_messages'); ?>
        <?php $this->load->view($inner_view, $data ?? []); ?>
    <?php else: ?>
        <p class="text-red-500">Ошибка: Внутреннее представление не задано.</p>
    <?php endif; ?>
</div>

<?php if (!empty($right_sidebar_view)): ?>
<aside class="flex-shrink-0" style="width: 240px;">
    <?php $this->load->view($right_sidebar_view, $data ?? []); ?>
</aside>
<?php endif; ?>
