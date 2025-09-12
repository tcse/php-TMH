document.addEventListener('DOMContentLoaded', function() {
    // === Telegram WebApp ===
    if (window.Telegram?.WebApp) {
        window.Telegram.WebApp.ready();
        window.Telegram.WebApp.expand();
        document.documentElement.style.setProperty('--accent', window.Telegram.WebApp.themeParams.accentTextColor || '#1DB954');
        document.documentElement.style.setProperty('--primary-bg', window.Telegram.WebApp.themeParams.bgColor || '#121212');
        if (window.Telegram.WebApp.BackButton) {
            window.Telegram.WebApp.BackButton.show();
            window.Telegram.WebApp.BackButton.onClick(() => {
                const playlist = document.getElementById('playlistContainer');
                if (playlist.style.display === 'flex') {
                    playlist.style.display = 'none';
                } else {
                    window.Telegram.WebApp.close();
                }
            });
        }
    }

    const loadingScreen = document.getElementById('loadingScreen');
    const errorMessage = document.getElementById('errorMessage');
    const openPlaylistBtn = document.getElementById('openPlaylistBtn');
    const closePlaylistBtn = document.getElementById('closePlaylistBtn');
    const playlistContainer = document.getElementById('playlistContainer');
    const portraitPlaylistTracks = document.getElementById('portraitPlaylistTracks');
    const portraitPlaylistGenre = document.getElementById('portraitPlaylistGenre');
    const portraitPlaylistUser = document.getElementById('portraitPlaylistUser');
    const portraitPlaylistMy = document.getElementById('portraitPlaylistMy');
    const tabButtons = document.querySelectorAll('[data-bs-tab]');

    let audio = null;
    let currentTrackIndex = 0;
    let isPlaying = false;
    let isShuffled = false;
    let repeatMode = 2;
    let playlist = [];
    let shuffledIndices = [];

    // Элементы управления
    const playPauseBtn = document.getElementById('playPauseBtn');
    const shuffleBtn = document.getElementById('shuffleBtn');
    const repeatBtn = document.getElementById('repeatBtn');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const progressBar = document.querySelector('.progress');
    const progress = document.querySelector('.progress-bar');
    const currentTime = document.querySelector('.current-time');
    const totalTime = document.querySelector('.total-time');

    // Формат времени
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
    }

    // Создание аудио
    function createAudioElement() {
        if (audio) {
            audio.pause();
            audio = null;
        }
        audio = new Audio();
        audio.addEventListener('timeupdate', updateProgress);
        audio.addEventListener('loadedmetadata', updateDuration);
        audio.addEventListener('ended', handleTrackEnd);
        return audio;
    }

    // Прогресс
    function updateProgress() {
        if (audio && !isNaN(audio.duration)) {
            const progressPercent = (audio.currentTime / audio.duration) * 100;
            progress.style.width = `${progressPercent}%`;
            currentTime.textContent = formatTime(audio.currentTime);
        }
    }

    // Длительность
    function updateDuration() {
        if (audio && !isNaN(audio.duration)) {
            totalTime.textContent = formatTime(audio.duration);
        }
    }

    // Конец трека
    function handleTrackEnd() {
        if (repeatMode === 1) {
            audio.currentTime = 0;
            audio.play();
        } else {
            playNextTrack();
        }
    }

    // Воспроизведение
    function playTrack(index) {
        // Обновление Media Session
        if ('mediaSession' in navigator) {
            try {
                const track = playlist[index];
                navigator.mediaSession.metadata = new MediaMetadata({
                    title: track.title,
                    artist: track.performer || 'Неизвестный',
                    artwork: [{ 
                        src: track.cover || 'https://placehold.co/512x512/121212/ffffff?text=TMH+by+TCSE', 
                        sizes: '512x512', 
                        type: 'image/png' 
                    }]
                });
            } catch (e) {
                console.warn('Media Session ошибка:', e);
            }
        }

        if (index < 0 || index >= playlist.length) return;
        currentTrackIndex = index;
        const track = playlist[index];

        if (!audio) createAudioElement();
        audio.src = track.url;
        updateTrackInfo(track);
        updateActiveTrack();

        audio.play().catch(e => console.error("Ошибка воспроизведения:", e));
        isPlaying = true;
        updatePlayPauseButton();

        // Увеличение счётчика прослушиваний
        fetch('/tmh/core/update_play.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ file_id: track.id })
        }).catch(err => console.warn('Не удалось обновить счётчик:', err));
    }

    // Информация о треке
    function updateTrackInfo(track) {
        document.querySelectorAll('.track-title').forEach(el => el.textContent = track.title);
        document.querySelectorAll('.track-artist').forEach(el => el.textContent = track.performer || 'Неизвестный');
        const coverUrl = track.cover || `https://placehold.co/400x400/${getRandomColor()}/ffffff?text=${encodeURIComponent(track.title.charAt(0).toUpperCase())}`;
        document.querySelectorAll('.album-art img').forEach(img => img.src = coverUrl);
        document.querySelector('.background-blur').style.backgroundImage = `url('${coverUrl}')`;
    }

    // Подсветка активного трека
    function updateActiveTrack() {
        document.querySelectorAll('.track-item').forEach(t => t.classList.remove('active'));
        const activeTrack = playlist[currentTrackIndex];
        if (!activeTrack) return;

        const elements = document.querySelectorAll('.track-item');
        elements.forEach(el => {
            const url = el.querySelector('.download-btn')?.getAttribute('data-url');
            if (url === activeTrack.url) {
                el.classList.add('active');
            }
        });
    }

    // Кнопка play/pause
    function updatePlayPauseButton() {
        const icon = isPlaying ? '<i class="bi bi-pause-fill"></i>' : '<i class="bi bi-play-fill"></i>';
        playPauseBtn.innerHTML = icon;
    }

    // Следующий/предыдущий
    function playNextTrack() {
        let nextIndex;
        if (isShuffled && shuffledIndices.length > 0) {
            const i = shuffledIndices.indexOf(currentTrackIndex);
            nextIndex = i >= 0 && i < shuffledIndices.length - 1 ? shuffledIndices[i + 1] : shuffledIndices[0];
        } else {
            nextIndex = (currentTrackIndex + 1) % playlist.length;
        }
        playTrack(nextIndex);
    }

    function playPrevTrack() {
        let prevIndex;
        if (isShuffled && shuffledIndices.length > 0) {
            const i = shuffledIndices.indexOf(currentTrackIndex);
            prevIndex = i > 0 ? shuffledIndices[i - 1] : shuffledIndices[shuffledIndices.length - 1];
        } else {
            prevIndex = currentTrackIndex > 0 ? currentTrackIndex - 1 : playlist.length - 1;
        }
        playTrack(prevIndex);
    }

    // Перемешивание
    function toggleShuffle() {
        isShuffled = !isShuffled;
        if (isShuffled) {
            shuffledIndices = [...Array(playlist.length).keys()];
            for (let i = shuffledIndices.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [shuffledIndices[i], shuffledIndices[j]] = [shuffledIndices[j], shuffledIndices[i]];
            }
            shuffleBtn.classList.add('active');
        } else {
            shuffledIndices = [];
            shuffleBtn.classList.remove('active');
        }
    }

    // Повтор
    function toggleRepeat() {
        repeatMode = (repeatMode + 1) % 3;
        repeatBtn.classList.remove('active');
        repeatBtn.querySelector('i').classList.remove('bi-repeat-1', 'bi-repeat');
        if (repeatMode === 1) {
            repeatBtn.classList.add('active');
            repeatBtn.querySelector('i').classList.add('bi-repeat-1');
        } else if (repeatMode === 2) {
            repeatBtn.classList.add('active');
            repeatBtn.querySelector('i').classList.add('bi-repeat');
        }
    }

    // Элемент трека
    function createTrackElement(track, index, isFiltered = false) {
        const el = document.createElement('div');
        el.className = 'track-item';
        if (index === currentTrackIndex) el.classList.add('active');

        const cover = track.cover || `https://placehold.co/100x100/${getRandomColor()}/ffffff?text=${track.title.charAt(0).toUpperCase()}`;
        el.innerHTML = `
            <div class="track-thumbnail">
                <img src="${cover}" alt="Обложка">
            </div>
            <div class="track-details">
                <div class="track-name">${track.title}</div>
                <div class="track-artist-mini">${track.performer || 'Неизвестный'}</div>
            </div>
            <div class="track-actions">
                <button class="action-btn download-btn" title="Скачать" data-url="${track.url}"><i class="bi bi-download"></i></button>
                <button class="action-btn like-btn" title="В избранное" data-id="${track.id}"><i class="bi ${isTrackLiked(track.id) ? 'bi-heart-fill' : 'bi-heart'}"></i></button>
            </div>
        `;

        el.addEventListener('click', () => {
            if (currentTrackIndex === index && isPlaying) {
                togglePlayPause();
            } else {
                const realIndex = isFiltered ? playlist.findIndex(t => t.id === track.id) : index;
                if (realIndex !== -1) playTrack(realIndex);
            }
        });

        // Скачать
        el.querySelector('.download-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            const a = document.createElement('a');
            a.href = track.url;
            a.download = `${track.title} - ${track.performer}.mp3`;
            a.click();
        });

        // Лайк
        el.querySelector('.like-btn').addEventListener('click', (e) => {
            e.stopPropagation();
            toggleLike(track.id);
            const icon = el.querySelector('.like-btn i');
            if (isTrackLiked(track.id)) {
                icon.classList.replace('bi-heart', 'bi-heart-fill');
            } else {
                icon.classList.replace('bi-heart-fill', 'bi-heart');
            }
        });

        return el;
    }

    // Элемент категории
    function createCategoryElement(name, count, onClick) {
        const el = document.createElement('div');
        el.className = 'category-item';
        const color = ['121212', '3498db', 'e74c3c', '2ecc71', '9b59b6'][Math.floor(Math.random() * 5)];
        el.innerHTML = `
            <div class="category-thumbnail">
                <img src="https://placehold.co/100x100/${color}/ffffff?text=${name.charAt(0).toUpperCase()}" alt="Иконка">
            </div>
            <div class="category-details">
                <div class="category-name">${name}</div>
                <div class="category-count">${count} треков</div>
            </div>
        `;
        el.addEventListener('click', onClick);
        return el;
    }

    // Загрузка плейлиста
    async function loadPlaylist() {
        // Media Session API (для Android)
        if ('mediaSession' in navigator) {
            navigator.mediaSession.setActionHandler('play', () => togglePlayPause());
            navigator.mediaSession.setActionHandler('pause', () => togglePlayPause());
            navigator.mediaSession.setActionHandler('previoustrack', () => playPrevTrack());
            navigator.mediaSession.setActionHandler('nexttrack', () => playNextTrack());
        }

        try {
            const response = await fetch('/tmh/core/proxy.php');
            if (!response.ok) throw new Error(`Ошибка: ${response.status}`);

            const data = await response.json();
            if (data.error) throw new Error(data.error);

            playlist = data.tracks;
            if (playlist.length === 0) throw new Error('Нет треков');

            // Заполнение треков
            portraitPlaylistTracks.innerHTML = '';
            playlist.forEach((track, i) => {
                portraitPlaylistTracks.appendChild(createTrackElement(track, i));
            });

            // Жанры
            const genreCount = {};
            playlist.forEach(t => {
                const genreString = t.genre || 'Unknown';
                const genres = genreString.split(',')
                    .map(g => g.trim().toLowerCase())
                    .filter(g => g.length > 0);
                genres.forEach(g => genreCount[g] = (genreCount[g] || 0) + 1);
            });
            const sortedGenres = Object.entries(genreCount).sort((a,b) => b[1]-a[1]).slice(0,20);
            portraitPlaylistGenre.innerHTML = '';
            sortedGenres.forEach(([g, c]) => {
                const onClick = () => {
                    const filtered = playlist.filter(t => {
                        const gs = (t.genre || 'Unknown').split(',').map(x => x.trim().toLowerCase());
                        return gs.includes(g);
                    });
                    updateTrackList(filtered, true);
                    showTab('portraitPlaylistTracks');
                };
                portraitPlaylistGenre.appendChild(createCategoryElement(g, c, onClick));
            });

            // Пользователи
            const userCount = {};
            playlist.forEach(t => {
                const u = t.username || '';
                const key = u ? u : (t.user_uploader ? `user_${t.user_uploader}` : 'Unknown');
                userCount[key] = (userCount[key] || 0) + 1;
            });
            const sortedUsers = Object.entries(userCount).sort((a,b) => b[1]-a[1]).slice(0,20);
            portraitPlaylistUser.innerHTML = '';
            sortedUsers.forEach(([u, c]) => {
                const onClick = () => {
                    const filtered = playlist.filter(t => {
                        const key = t.username ? t.username : `user_${t.user_uploader}`;
                        return key === u;
                    });
                    updateTrackList(filtered, true);
                    showTab('portraitPlaylistTracks');
                };
                portraitPlaylistUser.appendChild(createCategoryElement(u, c, onClick));
            });

            // Мои треки
            portraitPlaylistMy.innerHTML = '';
            const myId = window.Telegram?.WebApp?.initDataUnsafe?.user?.id || '757940529';
            if (myId) {
                const myTracks = playlist.filter(t => String(t.user_uploader) === String(myId));
                if (myTracks.length > 0) {
                    const onClick = () => {
                        updateTrackList(myTracks, true);
                        showTab('portraitPlaylistTracks');
                    };
                    portraitPlaylistMy.appendChild(createCategoryElement('Мои треки', myTracks.length, onClick));
                } else {
                    const no = document.createElement('div');
                    no.textContent = 'У вас нет треков';
                    no.style.textAlign = 'center';
                    no.style.padding = '1rem';
                    no.style.color = 'var(--text-secondary)';
                    portraitPlaylistMy.appendChild(no);
                }

                // Кнопка "Все треки"
                const allBtn = document.createElement('div');
                allBtn.className = 'category-item';
                allBtn.innerHTML = `
                    <div class="category-thumbnail">
                        <img src="https://placehold.co/512x512/f39c12/ffffff?text=ALL" alt="Все">
                    </div>
                    <div class="category-details">
                        <div class="category-name">Все треки</div>
                        <div class="category-count">${playlist.length} треков</div>
                    </div>
                `;
                allBtn.addEventListener('click', () => {
                    updateTrackList(playlist, false);
                    showTab('portraitPlaylistTracks');
                });
                portraitPlaylistMy.appendChild(allBtn);
            }

            // Избранное
            if (getLikedTracks().length > 0) {
                const likedBtn = document.createElement('div');
                likedBtn.className = 'category-item';
                likedBtn.innerHTML = `
                    <div class="category-thumbnail">
                        <img src="https://placehold.co/512x512/3498db/ffffff?text=Liked" alt="Избранное">
                    </div>
                    <div class="category-details">
                        <div class="category-name">Избранное</div>
                        <div class="category-count">${getLikedTracks().length} треков</div>
                    </div>
                `;
                likedBtn.addEventListener('click', () => {
                    const likedTracks = playlist.filter(t => isTrackLiked(t.id));
                    updateTrackList(likedTracks, true);
                    showTab('portraitPlaylistTracks');
                });
                portraitPlaylistMy.appendChild(likedBtn);
            }

            // Случайный старт
            const randomIndex = Math.floor(Math.random() * playlist.length);
            playTrack(randomIndex);

            // Табы
            setupTabs();
            loadingScreen.style.display = 'none';
        } catch (error) {
            console.error('Ошибка загрузки плейлиста:', error);
            loadingScreen.style.display = 'none';
            errorMessage.style.display = 'block';
            setTimeout(() => errorMessage.style.display = 'none', 5000);
        }
    }

    // Табы
    function setupTabs() {
        tabButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.getAttribute('data-bs-tab');
                showTab(tabId);
            });
        });
    }

    function showTab(tabId) {
        tabButtons.forEach(btn => btn.classList.remove('active'));
        const activeBtn = document.querySelector(`[data-bs-tab="${tabId}"]`);
        if (activeBtn) activeBtn.classList.add('active');
        document.querySelectorAll('.playlist-panel').forEach(p => p.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
    }

    // Обновление списка
    function updateTrackList(filtered, isFiltered = false) {
        portraitPlaylistTracks.innerHTML = '';
        filtered.forEach((track, i) => {
            portraitPlaylistTracks.appendChild(createTrackElement(track, i, isFiltered));
        });
    }

    // Генерация цвета
    function getRandomColor() {
        return ['121212', '3498db', 'e74c3c', '2ecc71', '9b59b6', 'f39c12', '1abc9c', 'd35400'][Math.floor(Math.random() * 8)];
    }

    // Воспроизведение/пауза
    function togglePlayPause() {
        if (!audio) {
            playTrack(currentTrackIndex);
            return;
        }
        if (isPlaying) {
            audio.pause();
        } else {
            audio.play().catch(e => console.error("Ошибка воспроизведения:", e));
        }
        isPlaying = !isPlaying;
        updatePlayPauseButton();
    }

    // --- Избранное ---
    function getLikedTracks() {
        const liked = localStorage.getItem('tmh_liked_tracks');
        return liked ? JSON.parse(liked) : [];
    }

    function isTrackLiked(id) {
        return getLikedTracks().includes(id);
    }

    function toggleLike(id) {
        const liked = getLikedTracks();
        const index = liked.indexOf(id);
        if (index === -1) {
            liked.push(id);
        } else {
            liked.splice(index, 1);
        }
        localStorage.setItem('tmh_liked_tracks', JSON.stringify(liked));
    }

    // Обработчики
    function setupEventListeners() {
        playPauseBtn.addEventListener('click', togglePlayPause);
        shuffleBtn.addEventListener('click', toggleShuffle);
        repeatBtn.addEventListener('click', toggleRepeat);
        prevBtn.addEventListener('click', playPrevTrack);
        nextBtn.addEventListener('click', playNextTrack);
        progressBar.addEventListener('click', e => {
            if (audio && !isNaN(audio.duration)) {
                const pos = (e.offsetX / progressBar.offsetWidth);
                audio.currentTime = pos * audio.duration;
            }
        });
        openPlaylistBtn.addEventListener('click', () => playlistContainer.style.display = 'flex');
        closePlaylistBtn.addEventListener('click', () => playlistContainer.style.display = 'none');

        // Управление с клавиатуры
        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            switch (e.code) {
                case 'ArrowLeft':
                    e.preventDefault();
                    playPrevTrack();
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    playNextTrack();
                    break;
            }
        });
    }

    // Инициализация
    function initPlayer() {
        createAudioElement();
        setupEventListeners();
        loadPlaylist();
    }

    initPlayer();
});
