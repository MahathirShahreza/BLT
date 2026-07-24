    </main><!-- /main-content -->

    <!-- FOOTER -->
    <footer class="main-footer">
        <span><?= APP_NAME ?> &copy; <?= APP_TAHUN ?></span>
        <span class="ms-2 text-muted">v<?= APP_VERSION ?></span>
    </footer>
</div><!-- /main-wrapper -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
// Sidebar toggle
document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.getElementById('mainWrapper').classList.toggle('expanded');
});
// Auto-hide alerts
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        let bsAlert = bootstrap.Alert.getOrCreateInstance(a);
        bsAlert.close();
    });
}, 4000);
// Confirm delete
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function(e) {
        if (!confirm(this.dataset.confirm || 'Apakah Anda yakin?')) e.preventDefault();
    });
});
</script>
<?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>
