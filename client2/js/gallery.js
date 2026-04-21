

let currentPage = 1;     
let hasMore     = true;  
let loading     = false; 
let playingId   = null;  
let playingBtn  = null;  

const grid        = document.getElementById('masonryGrid');
const loadMoreBtn = document.getElementById('loadMoreBtn');
const nowPlaying  = document.getElementById('nowPlaying');
const npTitle     = document.getElementById('npTitle');
const npThumb     = document.getElementById('npThumb');
const audio       = document.getElementById('mainAudio');
const volSlider   = document.getElementById('volSlider');


volSlider.addEventListener('input', () => {
    audio.volume = parseFloat(volSlider.value);
});
audio.volume = parseFloat(volSlider.value);

audio.addEventListener('ended', () => stopAudio());
audio.addEventListener('error', () => stopAudio());

/**
 * Loads and plays an audio track, updating the now-playing bar.
 *
 * @param {string|number} id       Unique ID of the card being played (or 'spotlight')
 * @param {string}        src      URL of the audio file
 * @param {string}        title    Caption to display in the now-playing bar
 * @param {string}        thumbSrc Image URL to show as album art in the bar
 */
function playTrack(id, src, title, thumbSrc) {
    if (playingId === id) { stopAudio(); return; }

    stopAudio(false);

    audio.src = src;
    audio.volume = parseFloat(volSlider.value);
    audio.play().catch(() => {
        // Browser blocked autoplay, prompts the user to try again
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

/**
 * Pauses audio and resets all playing state.
 *
 * @param {boolean} hideBar - Whether to slide the now-playing bar back down (default true)
 */
function stopAudio(hideBar = true) {
    audio.pause();
    audio.currentTime = 0;
    playingId = null;

    if (playingBtn) {
        playingBtn.classList.remove('playing');
        playingBtn = null;
    }

    const sp = document.getElementById('spotlightPlay');
    sp.classList.remove('playing');
    sp.textContent = '▶';

    if (hideBar) nowPlaying.classList.remove('visible');
}

document.getElementById('npStop').addEventListener('click', () => stopAudio());


/**
 * Fetches one page of images from gallery_data.php and appends each to the grid.
 *
 * @param {number} page - 1-based page index to request
 */
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


/**
 * Creates one masonry card element and appends it to the grid.
 * Attaches play button logic if the image has an audio track.
 *
 * @param {Object}      img         - Image data object returned by gallery_data.php
 * @param {number}      img.id      - Unique image ID
 * @param {string}      img.file    - Filename (e.g. macengjazz-20.jpg)
 * @param {string}      img.caption - Short display caption
 * @param {string}      img.tag     - Category label (e.g. "performance")
 * @param {string}      img.span    - Grid size hint: "tall" | "wide" | "normal"
 * @param {string|null} img.music   - Audio file path, or null if no track assigned
 */
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

    const imgEl   = item.querySelector('img');
    const playBtn = item.querySelector('.card-play-btn');

    imgEl.addEventListener('load',  () => item.classList.remove('skeleton'));
    imgEl.addEventListener('error', () => item.classList.remove('skeleton'));

    if (hasMusic) {
        playBtn.addEventListener('click', e => {
            e.stopPropagation();

            if (playingId === img.id) {
                stopAudio();
                playBtn.classList.remove('playing');
                playingBtn = null;
            } else {
                if (playingBtn) playingBtn.classList.remove('playing');
                playingBtn = playBtn;
                playBtn.classList.add('playing');
                playTrack(img.id, playBtn.dataset.music, playBtn.dataset.title, playBtn.dataset.thumb);
            }
        });
    }

    grid.appendChild(item);
}


const spotlightEl   = document.getElementById('spotlight');
const spotlightPlay = document.getElementById('spotlightPlay');

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
        playingId  = 'spotlight';
        spotlightPlay.textContent = '❚❚';
        spotlightPlay.classList.add('playing');
        playTrack('spotlight', src, title, thumb);
    }
});


// loads the next page automatically when the bottom of the page comes into view
const sentinel = document.querySelector('.load-more-wrap');
const observer = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting && !loading && hasMore) {
        fetchImages(currentPage);
    }
}, { rootMargin: '200px' });

observer.observe(sentinel);
loadMoreBtn.addEventListener('click', () => fetchImages(currentPage));


/**
 * Escapes a string so it is safe to inject into HTML attributes or text nodes.
 *
 * @param {string} str - Raw string to escape
 * @returns {string} HTML-encoded version of the input
 */
function escHtml(str) {
    return String(str)
        .replace(/&/g,  '&amp;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/"/g,  '&quot;');
}

fetchImages(1);
