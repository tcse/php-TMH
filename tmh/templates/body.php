<!-- Подвал -->
<?php include __DIR__ . '/footer.php'; ?>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    // Тема
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = themeToggle.querySelector('i');
    const body = document.body;
    const savedTheme = localStorage.getItem('blog-theme') || 'dark';
    setTheme(savedTheme);
    themeToggle.addEventListener('click', () => {
        const newTheme = body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
    });
    function setTheme(theme) {
        body.setAttribute('data-theme', theme);
        localStorage.setItem('blog-theme', theme);
        themeIcon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
    }

    // Swiper
    document.addEventListener('DOMContentLoaded', () => {
        if (document.querySelector('.swiper-container')) {
            new Swiper('.swiper-container', {
                loop: false,
                navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
                pagination: { el: '.swiper-pagination', clickable: true },
                autoplay: { delay: 5000, disableOnInteraction: false }
            });
        }
    });
</script>
</body>
</html>