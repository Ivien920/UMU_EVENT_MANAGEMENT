<?php // UMU Events — Footer ?>
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
           
            
            <div>
                <p class="footer-name">Uganda Martyrs University</p>
                <p class="footer-sub">Masaka, Uganda &bull; Est. 2006</p>
            </div>
        </div>
        <p class="footer-copy">&copy; <?= date('Y') ?> UMU Events Management System &mdash; All rights reserved.</p>
    </div>
</footer>

<script>
// User dropdown toggle
document.querySelector('.user-menu')?.addEventListener('click', function(e) {
    e.stopPropagation();
    this.classList.toggle('open');
});
document.addEventListener('click', () => {
    document.querySelector('.user-menu')?.classList.remove('open');
});
</script>
</body>
</html>
