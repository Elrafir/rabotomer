<div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden sticky top-4">
    <div class="p-6 border-b border-gray-50 bg-gray-50">
        <h3 class="font-black text-gray-800 text-lg"><?= lang('nav_stats_calc'); ?></h3>
    </div>
    <nav class="flex flex-col p-2 gap-1">
        <a href="<?= site_url('reports') ?>" class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-colors <?= current_url() == site_url('reports') ? 'bg-blue-50 text-blue-600 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            <?= lang('nav_reports'); ?>
        </a>
        
        <a href="<?= site_url('calculations') ?>" class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-colors <?= strpos(current_url(), site_url('calculations')) === 0 ? 'bg-blue-50 text-blue-600 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            <?= lang('nav_calculations'); ?>
        </a>
        
        <a href="<?= site_url('history') ?>" class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-colors <?= current_url() == site_url('history') ? 'bg-blue-50 text-blue-600 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <?= lang('nav_history'); ?>
        </a>
		
    </nav>
</div>
