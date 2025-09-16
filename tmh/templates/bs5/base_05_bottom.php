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
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    },
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                    },
                    slidesPerView: 1,
                    spaceBetween: 10,
                    autoplay: {
                        delay: 6000,
                        disableOnInteraction: false,
                    },
                    breakpoints: {
                        640: { slidesPerView: 1.2 },
                        768: { slidesPerView: 1.5 },
                        1024: { slidesPerView: 2.3 }
                    }
            });
        }
    });
</script>

<script>
document.querySelectorAll('.youtube-play-overlay + img').forEach(img => {
    const videoId = new URLSearchParams(new URL(img.src).search).get('v');
    if (!videoId) return;
    
    const maxres = `https://img.youtube.com/vi/${videoId}/maxresdefault.jpg`;
    const hq = `https://img.youtube.com/vi/${videoId}/0.jpg`;
    
    const testImg = new Image();
    testImg.onload = () => img.src = maxres;
    testImg.onerror = () => {}; // уже загружено 0.jpg
    testImg.src = maxres;
});
</script>
</body>
</html>