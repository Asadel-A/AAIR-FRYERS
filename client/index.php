<?php
include 'includes/auth_check.php';
?>
<!DOCTYPE html>
<html>

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
            color: #a42844 !important;
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
            <li><a href="attendance.php">Attendance</a></li>

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

            <?php
            $conn = new mysqli("localhost", "root", "", "jazz_band");

            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $sql = "
            SELECT 
                m.instrument,

                SUM(a.present) AS total_attended,

                ROUND(
                    (SUM(a.present) * 100.0) /
                    NULLIF(
                        (COUNT(DISTINCT m.id) * (SELECT COUNT(*) FROM events)),
                    0)
                , 1) AS percentage

            FROM members m
            JOIN attendance a ON m.id = a.member_id

            GROUP BY m.instrument
            ORDER BY percentage DESC
            LIMIT 5
            ";

            $result = $conn->query($sql);

            if (!$result) {
                die("SQL Error: " . $conn->error);
            }

            $rank = 1;

            while($row = $result->fetch_assoc()):
            ?>
                <div class="board-row">
                    
                    <!-- Medal / Rank -->
                    <span>
                        <?php 
                        echo $rank == 1 ? "🥇" : ($rank == 2 ? "🥈" : ($rank == 3 ? "🥉" : $rank));
                        ?>
                    </span>

                    <!-- Instrument -->
                    <span>
                        <?php echo htmlspecialchars($row['instrument']); ?>
                    </span>

                    <!-- Percentage + Total Attendance -->
                    <span class="num-ppl">
                        <?php echo number_format($row['percentage'], 1); ?>%
                        <span style="font-size: 0.8rem; color: #aaa;">
                            (<?php echo $row['total_attended']; ?>)
                        </span>
                    </span>

                </div>
            <?php 
            $rank++;
            endwhile; 
            ?>

        </div>
    </section>

    <footer id="contact">
        <p>Inquiries: his insta</p>
        <p>&copy; 2026 Mac Eng Jazz</p>
    </footer>

</body>

</html>