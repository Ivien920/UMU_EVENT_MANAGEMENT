<?php // UMU Events — Admin Footer ?>
    </div><!-- /.admin-body -->
</div><!-- /.admin-content -->
</div><!-- /.admin-wrap -->

<footer class="admin-footer-bar">
    <p>&copy; <?= date('Y') ?> Uganda Martyrs University &mdash; Events Management System</p>
</footer>

<script>
// Confirm delete
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
        if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
});
</script>
</body>
</html>
