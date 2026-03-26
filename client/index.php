<?php
include 'includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>McMaster Engineering Jazz Band</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .user-info {
            color: #fff;
            margin-right: 20px;
            font-size: 0.9rem;
        }

        .admin-link {
            color: #ffcc00 !important;
            font-weight: bold;
        }

        .logout-btn {
            color: #ff4d4d !important;
            margin-left: 15px;
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="logo">Mac Eng Jazz</div>
        <ul class="nav-links">
            <li class="user-info">Hi,
                <?php echo htmlspecialchars($_SESSION['username']); ?>!
            </li>

            <li><a href="#about">About</a></li>
            <li><a href="#shows">Attendance</a></li>

            <?php if ($_SESSION['role'] === 'admin'): ?>
            <li><a href="admin_dashboard.php" class="admin-link">Admin Panel</a></li>
            <?php
endif; ?>

            <li><a href="#contact" class="btn-gold">Team</a></li>
            <li><a href="#contact" class="btn-gold">Gallery</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <header class="hero">
        <div class="hero-content">
            <h1>Smooth, <br><span>Memorable</span></h1>
            <p>We are the best jazz band at McMaster..</p>
            <a href="#shows" class="btn-main">Attendance!</a>
        </div>
    </header>

    <section id="about" class="section">
        <div class="container">
            <h2>Mac Eng Jazz</h2>
            <p>Crazy description on how cracked they are and their vibes maybe, as well as their goals?</p>
        </div>
    </section>

    <section id="shows" class="leaderboard dark-bg">
        <div class="container">
            <h2>Leader Board 👑</h2>
            <div class="board-row">
                <span>1</span>
                <span>Pianos</span>
                <a href="#" class="num-ppl">14</a>
            </div>
            <div class="board-row">
                <span>2</span>
                <span>Tubas</span>
                <a href="#" class="num-ppl">12</a>
            </div>
            <div class="board-row">
                <span>3</span>
                <span>Saxophones</span>
                <span class="num-ppl">7</span>
            </div>
        </div>
    </section>

    <footer id="contact">
        <p>Inquiries: his insta</p>
        <p>&copy; 2026 Mac Eng Jazz</p>
    </footer>

</body>

</html>