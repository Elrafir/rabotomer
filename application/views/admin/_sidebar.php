<!-- Боковое меню панели управления -->
<div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-4 space-y-1">
    <!-- Заголовок — скрывается при коллапсе через CSS -->
    <h3 class="text-lg font-black text-gray-800 px-4 pt-2 pb-4 whitespace-nowrap">Панель управления</h3>
    
    <!-- Профиль — доступен ВСЕМ авторизованным пользователям -->
    <a href="<?= site_url('admin/profile') ?>" title="Мой профиль"
       class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-colors <?= ($admin_page === 'profile') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
        <span class="flex-shrink-0 text-lg">👤</span>
        <span class="sidebar-label whitespace-nowrap">Мой профиль</span>
    </a>
    
    <?php if (!empty($is_admin)): ?>
        <!-- Разделитель для admin-секции -->
        <div class="border-t border-gray-100 my-2"></div>
        <p class="px-4 text-xs font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">Администрирование</p>
        
        <!-- Пользователи -->
        <a href="<?= site_url('admin/users') ?>" title="Пользователи"
           class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-colors <?= ($admin_page === 'users') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <span class="flex-shrink-0 text-lg">👥</span>
            <span class="sidebar-label whitespace-nowrap">Пользователи</span>
        </a>
        
        <!-- Резервные копии -->
        <a href="<?= site_url('admin/backups') ?>" title="Резервные копии"
           class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-colors <?= ($admin_page === 'backups') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <span class="flex-shrink-0 text-lg">💾</span>
            <span class="sidebar-label whitespace-nowrap">Резервные копии</span>
        </a>
        
        <!-- Настройки -->
        <a href="<?= site_url('admin/settings') ?>" title="Настройки"
           class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-colors <?= ($admin_page === 'settings') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <span class="flex-shrink-0 text-lg">⚙️</span>
            <span class="sidebar-label whitespace-nowrap">Настройки</span>
        </a>
    <?php endif; ?>
</div>
