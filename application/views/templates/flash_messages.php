<div class="fixed top-20 right-5 z-[9999] flex flex-col gap-3 w-80 pointer-events-none">
<?php if ($this->session->flashdata('error')): ?>
    <div class="role-alert-flash bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-xl shadow-xl pointer-events-auto transition-all" role="alert">
        <span class="block sm:inline"><?php echo $this->session->flashdata('error'); ?></span>
    </div>
<?php endif; ?>

<?php if ($this->session->flashdata('success')): ?>
    <div class="role-alert-flash bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-xl shadow-xl pointer-events-auto transition-all" role="alert">
        <span class="block sm:inline"><?php echo $this->session->flashdata('success'); ?></span>
    </div>
<?php endif; ?>

<?php if ($this->session->flashdata('warning')): ?>
    <div class="role-alert-flash bg-yellow-100 border border-yellow-400 text-yellow-700 px-6 py-4 rounded-xl shadow-xl pointer-events-auto transition-all" role="alert">
        <span class="block sm:inline"><?php echo $this->session->flashdata('warning'); ?></span>
    </div>
<?php endif; ?>
</div>

<script>
    // Автоматически скрываем флеш-сообщения через 3 секунды
    if ($('.role-alert-flash').length) {
        setTimeout(function() {
            $('.role-alert-flash').fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
</script>
