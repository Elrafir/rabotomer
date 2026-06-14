<!-- Левая колонка (меню, виджеты) -->
<?php if (!empty($left_sidebar_view)){ ?>
    <aside class="w-full md:w-1/4 flex-shrink-0 sticky top-4">
        <?php $this->load->view($left_sidebar_view, $data ?? []); ?>
    </aside>
<?php }?>

<!-- Центральная колонка (основной контент) -->
<div class="flex-grow w-full min-w-0">
    <!-- Динамическая загрузка внутреннего представления с передачей всех данных -->
    <?php if (isset($inner_view)): ?>
        <?php if(isset($active_session)) $data['active_session'] = $active_session; ?>
        <?php $this->load->view('templates/flash_messages'); ?>
        <?php $this->load->view($inner_view, $data ?? []); ?>
    <?php else: ?>
        <p class="text-red-500">Ошибка: Внутреннее представление не задано.</p>
    <?php endif; ?>
</div>

<!-- Правая колонка (баннеры, виджеты) -->
<?php if (!empty($right_sidebar_view)): ?>
    <aside class="w-full md:w-1/4 flex-shrink-0 sticky top-4">
        <?php $this->load->view($right_sidebar_view, $data ?? []); ?>
    </aside>
<?php endif; ?>
