<?php
include 'includes/auth_check.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery — Mac Eng Jazz</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;1,300;1,600&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold:    #d4af37;
            --gold-dim:#8a6e1a;
            --bg:      #080809;
            --surface: #0f0f11;
            --border:  #1e1e22;
            --text:    #c8c0b4;
            --white:   #f0ece6;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; }

        .navbar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1.2rem 5%; background: rgba(8,8,9,0.95);
            position: sticky; top: 0; z-index: 200;
            border-bottom: 1px solid var(--border); backdrop-filter: blur(8px);
        }
        .logo { font-family: 'Cormorant Garamond', serif; font-size: 1.4rem; font-weight: 600; letter-spacing: 3px; color: var(--gold); }
        .nav-links { list-style: none; display: flex; gap: 24px; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--text); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1.5px; transition: color 0.2s; }
        .nav-links a:hover, .nav-links a.active { color: var(--gold); }
        .logout-btn { color: #884040 !important; }

        .gallery-header { padding: 70px 6% 40px; position: relative; overflow: hidden; }
        .gallery-header::before {
            content: 'GALLERY'; position: absolute; top: 10px; right: -10px;
            font-family: 'Cormorant Garamond', serif; font-size: clamp(80px,15vw,180px);
            font-weight: 300; color: transparent; -webkit-text-stroke: 1px rgba(212,175,55,0.07);
            letter-spacing: 20px; pointer-events: none; white-space: nowrap;
        }
        .gallery-header h1 { font-family: 'Cormorant Garamond', serif; font-size: clamp(2rem,5vw,3.5rem); font-weight: 300; font-style: italic; color: var(--white); line-height: 1.1; }
        .gallery-header h1 em { font-style: normal; color: var(--gold); }
        .gallery-header p { margin-top: 10px; font-size: 0.72rem; color: #555; letter-spacing: 2px; text-transform: uppercase; }
        .music-hint {
            display: inline-flex; align-items: center; gap: 8px; margin-top: 20px;
            font-size: 0.68rem; color: #444; letter-spacing: 1px; text-transform: uppercase;
            border: 1px solid #222; padding: 6px 14px; border-radius: 2px;
        }
        .music-hint .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--gold-dim); animation: pulse 2s ease-in-out infinite; }
        @keyframes pulse { 0%,100% { opacity: 0.3; } 50% { opacity: 1; } }

        .spotlight {
            margin: 0 6%; position: relative; overflow: hidden;
            border: 1px solid var(--border); cursor: pointer;
        }
        .spotlight img {
            width: 100%; height: clamp(320px,50vh,560px); object-fit: cover; display: block;
            transition: transform 0.7s cubic-bezier(0.25,0.46,0.45,0.94), filter 0.4s ease;
            filter: brightness(0.85) saturate(0.9);
        }
        .spotlight:hover img { transform: scale(1.03); filter: brightness(0.65) saturate(0.8); }
        .spotlight-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.75) 0%, transparent 50%); pointer-events: none; }
        .spotlight-info { position: absolute; bottom: 0; left: 0; right: 0; padding: 28px 32px; transform: translateY(4px); transition: transform 0.3s ease; }
        .spotlight:hover .spotlight-info { transform: translateY(0); }
        .spotlight-info .tag { font-size: 0.62rem; letter-spacing: 2px; text-transform: uppercase; color: var(--gold); margin-bottom: 6px; }
        .spotlight-info h2 { font-family: 'Cormorant Garamond', serif; font-size: clamp(1.2rem,3vw,2rem); font-weight: 300; color: var(--white); }

        .spotlight-play-btn {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%,-50%) scale(0.85);
            width: 64px; height: 64px; border-radius: 50%;
            border: 2px solid rgba(212,175,55,0.6);
            background: rgba(0,0,0,0.5);
            color: var(--gold); font-size: 1.3rem;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.25s, transform 0.25s;
            cursor: pointer; z-index: 2;
        }
        .spotlight:hover .spotlight-play-btn { opacity: 1; transform: translate(-50%,-50%) scale(1); }
        .spotlight-play-btn.playing { background: rgba(212,175,55,0.2); border-color: var(--gold); }

        .masonry-wrap { padding: 2px 6% 0; }
        .masonry { columns: 3; column-gap: 3px; }
        @media (max-width: 900px) { .masonry { columns: 2; } }
        @media (max-width: 560px) { .masonry { columns: 1; } }

        .masonry-item {
            break-inside: avoid; margin-bottom: 3px;
            position: relative; overflow: hidden;
            cursor: pointer; display: block; background: var(--surface);
        }
        .masonry-item.span-tall  img { aspect-ratio: 3/4; }
        .masonry-item.span-wide      { column-span: all; }
        .masonry-item.span-wide  img { aspect-ratio: 16/7; }
        .masonry-item.span-normal img { aspect-ratio: 4/3; }

        .masonry-item img {
            width: 100%; display: block; object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.25,0.46,0.45,0.94), filter 0.35s ease;
            filter: brightness(0.88) saturate(0.85);
        }
        .masonry-item:hover img { transform: scale(1.05); filter: brightness(0.55) saturate(0.7); }

        .hover-overlay {
            position: absolute; inset: 0;
            display: flex; flex-direction: column; justify-content: flex-end;
            padding: 18px;
            background: linear-gradient(to top, rgba(0,0,0,0.72) 0%, transparent 60%);
            opacity: 0; transition: opacity 0.3s ease; pointer-events: none;
        }
        .masonry-item:hover .hover-overlay { opacity: 1; }
        .hover-overlay .caption { font-family: 'Cormorant Garamond', serif; font-size: 1rem; font-weight: 300; color: var(--white); margin-bottom: 3px; }
        .hover-overlay .meta { font-size: 0.6rem; color: var(--gold); letter-spacing: 1.5px; text-transform: uppercase; }

        /* Play button */
        .card-play-btn {
            position: absolute; top: 12px; right: 12px;
            width: 38px; height: 38px; border-radius: 50%;
            border: 1.5px solid rgba(212,175,55,0.5);
            background: rgba(0,0,0,0.6);
            color: var(--gold); font-size: 0.85rem;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transform: scale(0.8);
            transition: opacity 0.25s, transform 0.25s, background 0.2s, border-color 0.2s;
            cursor: pointer;
            z-index: 3;
            pointer-events: none; 
        }
        .masonry-item:hover .card-play-btn { opacity: 1; transform: scale(1); }
        .card-play-btn.has-music { pointer-events: all; }
        .card-play-btn.playing {
            background: rgba(212,175,55,0.25);
            border-color: var(--gold);
            opacity: 1 !important;
            transform: scale(1) !important;
        }

        .card-play-btn.no-music { border-color: #333; color: #444; cursor: default; pointer-events: none; }

        .wave-bars {
            display: none; gap: 2px; align-items: flex-end; height: 14px;
        }
        .card-play-btn.playing .play-icon { display: none; }
        .card-play-btn.playing .wave-bars { display: flex; }
        .wave-bars span {
            display: block; width: 3px; background: var(--gold); border-radius: 1px;
            animation: wave 0.7s ease-in-out infinite alternate;
        }
        .wave-bars span:nth-child(1) { height: 6px;  animation-delay: 0s;    }
        .wave-bars span:nth-child(2) { height: 12px; animation-delay: 0.15s; }
        .wave-bars span:nth-child(3) { height: 8px;  animation-delay: 0.3s;  }
        @keyframes wave { from { transform: scaleY(0.35); } to { transform: scaleY(1); } }


        .masonry-item.skeleton { background: linear-gradient(90deg,#111 25%,#1a1a1a 50%,#111 75%); background-size: 200% 100%; animation: shimmer 1.4s infinite; }
        .masonry-item.skeleton img { opacity: 0; }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

        .load-more-wrap { text-align: center; padding: 40px; }
        #loadMoreBtn {
            background: transparent; border: 1px solid var(--gold-dim);
            color: var(--gold); padding: 12px 36px;
            font-family: 'DM Mono', monospace; font-size: 0.72rem;
            letter-spacing: 2px; text-transform: uppercase; cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        #loadMoreBtn:hover { background: var(--gold); color: #000; }
        #loadMoreBtn:disabled { opacity: 0.3; cursor: default; }

        #nowPlaying {
            position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%);
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 2px; padding: 14px 22px;
            display: flex; align-items: center; gap: 14px;
            font-size: 0.68rem; letter-spacing: 1px; color: var(--text);
            z-index: 500; transition: bottom 0.4s cubic-bezier(0.25,0.46,0.45,0.94);
            white-space: nowrap; min-width: 300px;
            box-shadow: 0 4px 30px rgba(0,0,0,0.6);
        }
        #nowPlaying.visible { bottom: 24px; }

        .np-album { width: 36px; height: 36px; object-fit: cover; border: 1px solid var(--border); flex-shrink: 0; }
        .np-album-placeholder { width: 36px; height: 36px; background: #1a1a1c; border: 1px solid var(--border); flex-shrink: 0; display:flex; align-items:center; justify-content:center; color:#333; font-size:1rem; }
        .np-info { flex: 1; min-width: 0; }
        .np-title { color: var(--white); font-size: 0.7rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .np-sub   { color: #555; font-size: 0.6rem; margin-top: 2px; }

        .np-vol { display: flex; align-items: center; gap: 6px; }
        .np-vol span { font-size: 0.7rem; color: #555; }
        #volSlider {
            -webkit-appearance: none; width: 70px; height: 2px;
            background: #333; border-radius: 2px; outline: none;
        }
        #volSlider::-webkit-slider-thumb {
            -webkit-appearance: none; width: 10px; height: 10px;
            border-radius: 50%; background: var(--gold); cursor: pointer;
        }

        .np-stop {
            background: none; border: 1px solid #333; color: #666;
            padding: 4px 10px; cursor: pointer; font-size: 0.6rem;
            letter-spacing: 1px; text-transform: uppercase;
            transition: color 0.2s, border-color 0.2s; flex-shrink: 0;
        }
        .np-stop:hover { color: var(--white); border-color: #666; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">Mac Eng Jazz</div>
    <ul class="nav-links">
        <li style="color:#fff; font-size:0.75rem">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?>!</li>
        <li><a href="index.php">Home</a></li>
        <li><a href="gallery.php" class="active">Gallery</a></li>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <li><a href="admin_dashboard.php" style="color:#ffcc00; font-weight:bold;">Admin</a></li>
        <?php endif; ?>
        <li><a href="logout.php" class="logout-btn">Logout</a></li>
    </ul>
</nav>

<div class="gallery-header">
    <h1>Spring Concert<br><em>2026</em></h1>
    <p>Mac Eng Jazz · TSH B128 · March 20, 2026</p>
    <div class="music-hint">
        <span class="dot"></span>
        Click ▶ on any photo to play its track
    </div>
</div>


<div class="spotlight" id="spotlight" data-id="3" data-music="" data-title="Mac Eng Jazz — Final Concert">
    <img src="images/gallery/macengjazz-27.jpg" alt="Full band — Spring Concert 2026" loading="eager">
    <div class="spotlight-overlay"></div>
    <button class="spotlight-play-btn" id="spotlightPlay" title="Play track">▶</button>
    <div class="spotlight-info">
        <div class="tag">Spring Concert · 2026</div>
        <h2>Mac Eng Jazz — Final Concert</h2>
    </div>
</div>


<div class="masonry-wrap" style="margin-top:3px;">
    <div class="masonry" id="masonryGrid"></div>
</div>

<div class="load-more-wrap">
    <button id="loadMoreBtn">Load more</button>
</div>


<div id="nowPlaying">
    <div class="np-album-placeholder" id="npThumb">♪</div>
    <div class="np-info">
        <div class="np-title" id="npTitle">—</div>
        <div class="np-sub">Mac Eng Jazz · Concerts </div>
    </div>
    <div class="np-vol">
        <span>🔈</span>
        <input type="range" id="volSlider" min="0" max="1" step="0.05" value="0.6">
    </div>
    <button class="np-stop" id="npStop">■ Stop</button>
</div>

<audio id="mainAudio" preload="none"></audio>

<script>

let currentPage   = 1;
let hasMore       = true;
let loading       = false;
let playingId     = null;  
let playingBtn    = null;   

const grid        = document.getElementById('masonryGrid');
const loadMoreBtn = document.getElementById('loadMoreBtn');
const nowPlaying  = document.getElementById('nowPlaying');
const npTitle     = document.getElementById('npTitle');
const npThumb     = document.getElementById('npThumb');
const audio       = document.getElementById('mainAudio');
const volSlider   = document.getElementById('volSlider');

volSlider.addEventListener('input', () => { audio.volume = parseFloat(volSlider.value); });
audio.volume = parseFloat(volSlider.value);

audio.addEventListener('ended', () => stopAudio());
audio.addEventListener('error', () => stopAudio());

function playTrack(id, src, title, thumbSrc) {
    if (playingId === id) { stopAudio(); return; }

    stopAudio(false);

    audio.src = src;
    audio.volume = parseFloat(volSlider.value);
    audio.play().catch(() => {
        npTitle.textContent = 'Click play again to start audio';
        nowPlaying.classList.add('visible');
    });

    playingId = id;

    npTitle.textContent = title;
    if (thumbSrc) {
        npThumb.innerHTML = `<img class="np-album" src="${escHtml(thumbSrc)}" alt="">`;
    } else {
        npThumb.innerHTML = '♪';
        npThumb.className = 'np-album-placeholder';
    }
    nowPlaying.classList.add('visible');
}

function stopAudio(hideBar = true) {
    audio.pause();
    audio.currentTime = 0;
    playingId = null;

    if (playingBtn) {
        playingBtn.classList.remove('playing');
        playingBtn = null;
    }
    document.getElementById('spotlightPlay').classList.remove('playing');
    document.getElementById('spotlightPlay').textContent = '▶';

    if (hideBar) nowPlaying.classList.remove('visible');
}

document.getElementById('npStop').addEventListener('click', () => stopAudio());

// the ajax
function fetchImages(page) {
    if (loading || !hasMore) return;
    loading = true;
    loadMoreBtn.disabled = true;
    loadMoreBtn.textContent = 'Loading…';

    fetch(`gallery_data.php?page=${page}`)
        .then(r => r.json())
        .then(data => {
            hasMore = data.has_more;
            data.images.forEach(img => appendCard(img));
            currentPage++;
            loading = false;
            loadMoreBtn.disabled = !hasMore;
            loadMoreBtn.textContent = hasMore ? 'Load more' : 'All photos loaded';
        })
        .catch(() => {
            loading = false;
            loadMoreBtn.disabled = false;
            loadMoreBtn.textContent = 'Retry';
        });
}

// masory card template
function appendCard(img) {
    const hasMusic = !!img.music;
    const item = document.createElement('div');
    item.className = `masonry-item span-${img.span} skeleton`;
    item.dataset.id = img.id;

    item.innerHTML = `
        <img src="images/gallery/${escHtml(img.file)}"
             alt="${escHtml(img.caption)}" loading="lazy">
        <div class="hover-overlay">
            <div class="caption">${escHtml(img.caption)}</div>
            <div class="meta">${escHtml(img.tag)}</div>
        </div>
        <button class="card-play-btn ${hasMusic ? 'has-music' : 'no-music'}"
                title="${hasMusic ? 'Play track' : 'No audio yet'}"
                data-music="${escHtml(img.music || '')}"
                data-title="${escHtml(img.caption)}"
                data-thumb="images/gallery/${escHtml(img.file)}">
            <span class="play-icon">${hasMusic ? '▶' : '♪'}</span>
            <span class="wave-bars"><span></span><span></span><span></span></span>
        </button>
    `;

    const imgEl  = item.querySelector('img');
    const playBtn = item.querySelector('.card-play-btn');

    imgEl.addEventListener('load',  () => item.classList.remove('skeleton'));
    imgEl.addEventListener('error', () => item.classList.remove('skeleton'));

    if (hasMusic) {
        playBtn.addEventListener('click', e => {
            e.stopPropagation(); // don't bubble to card

            const src   = playBtn.dataset.music;
            const title = playBtn.dataset.title;
            const thumb = playBtn.dataset.thumb;

            if (playingId === img.id) {
                stopAudio();
                playBtn.classList.remove('playing');
                playingBtn = null;
            } else {
                // Switch to this track
                if (playingBtn) playingBtn.classList.remove('playing');
                playingBtn = playBtn;
                playBtn.classList.add('playing');
                playTrack(img.id, src, title, thumb);
            }
        });
    }

    grid.appendChild(item);
}

const spotlightEl   = document.getElementById('spotlight');
const spotlightPlay = document.getElementById('spotlightPlay');

// Pull music src from data attribute
spotlightPlay.addEventListener('click', e => {
    e.stopPropagation();
    const src   = spotlightEl.dataset.music;
    const title = spotlightEl.dataset.title;
    const thumb = 'images/gallery/macengjazz-27.jpg';

    if (!src) {
        npTitle.textContent = title + ' (audio coming soon)';
        nowPlaying.classList.add('visible');
        return;
    }

    if (playingId === 'spotlight') {
        stopAudio();
        spotlightPlay.textContent = '▶';
        spotlightPlay.classList.remove('playing');
    } else {
        if (playingBtn) playingBtn.classList.remove('playing');
        playingBtn = null;
        playingId = 'spotlight';
        spotlightPlay.textContent = '❚❚';
        spotlightPlay.classList.add('playing');
        playTrack('spotlight', src, title, thumb);
    }
});


const sentinel = document.querySelector('.load-more-wrap');
const observer = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting && !loading && hasMore) fetchImages(currentPage);
}, { rootMargin: '200px' });
observer.observe(sentinel);

loadMoreBtn.addEventListener('click', () => fetchImages(currentPage));

function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

fetchImages(1);
</script>
</body>
</html>
