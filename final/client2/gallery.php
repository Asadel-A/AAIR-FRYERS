<!-- 
Name: Asadel Ali & Krishdeep 
Date: March 21, 2026
Description: Gallery page for Mac Eng Jazz website. Displays photos from concerts and events, 
with an integrated music player to play tracks associated with each photo. 
Admin users can manage the gallery content through the admin dashboard.
 
-->

<?php
include 'includes/auth_check.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery — Mac Eng Jazz</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;1,300;1,600&family=DM+Mono:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/gallery.css">
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

<div class="masonry-wrap" style="margin-top: 3px;">
    <div class="masonry" id="masonryGrid"></div>
</div>

<div class="load-more-wrap">
    <button id="loadMoreBtn">Load more</button>
</div>

<div id="nowPlaying">
    <div class="np-album-placeholder" id="npThumb">♪</div>
    <div class="np-info">
        <div class="np-title" id="npTitle">—</div>
        <div class="np-sub">Mac Eng Jazz · Concerts</div>
    </div>
    <div class="np-vol">
        <span>🔈</span>
        <input type="range" id="volSlider" min="0" max="1" step="0.05" value="0.6">
    </div>
    <button class="np-stop" id="npStop">■ Stop</button>
</div>

<audio id="mainAudio" preload="none"></audio>

<script src="js/gallery.js"></script>
</body>
</html>
