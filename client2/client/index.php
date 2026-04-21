<?php
include 'includes/auth_check.php';
require 'config/db.php';

/*Get last 2 events*/
$events = $pdo->query("
    SELECT id FROM events 
    ORDER BY event_date DESC 
    LIMIT 2
")->fetchAll(PDO::FETCH_COLUMN);

$latestEvent = $events[0] ?? null;
$prevEvent = $events[1] ?? null;

/*Leaderboard query (percentage-based)*/
$sql = "
SELECT 
    m.instrument,

    SUM(CASE 
        WHEN a.status IN ('Attending','Late') THEN 1 
        ELSE 0 
    END) AS total_attended,

    COUNT(a.id) AS total_records,

    ROUND(
        (
            SUM(CASE 
                WHEN a.status IN ('Attending','Late') THEN 1 
                ELSE 0 
            END) * 100.0
        ) / NULLIF(COUNT(a.id), 0),
    1) AS percentage

FROM members m
JOIN attendance a ON m.id = a.member_id

GROUP BY m.instrument
ORDER BY percentage DESC
LIMIT 5
";

$stmt = $pdo->query($sql);
$leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*Function to get attendance % for one event*/
function getEventPercentage($pdo, $instrument, $eventId)
{
    if (!$eventId)
        return null;

    $stmt = $pdo->prepare("
        SELECT 
            ROUND(
                (
                    SUM(CASE 
                        WHEN a.status IN ('Attending','Late') THEN 1 
                        ELSE 0 
                    END) * 100.0
                ) / NULLIF(COUNT(a.id), 0),
            1) AS pct
        FROM members m
        JOIN attendance a ON m.id = a.member_id
        WHERE m.instrument = :instrument
        AND a.event_id = :event_id
    ");

    $stmt->execute([
        'instrument' => $instrument,
        'event_id' => $eventId
    ]);

    return $stmt->fetchColumn();
}
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
            color: #d4af37 !important;
            font-weight: bold;
        }

        .logout-btn {
            color: #a42844 !important;
            margin-left: 15px;
        }

        .trend-up {
            color: #4caf50;
            margin-left: 6px;
        }

        .trend-down {
            color: #e53935;
            margin-left: 6px;
        }

        .trend-flat {
            color: #888;
            margin-left: 6px;
        }

        .num-ppl small {
            display: block;
            font-size: 0.75rem;
            color: #888;
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="logo">Mac Eng Jazz</div>
        <ul class="nav-links">
            <li class="user-info">Hi, <?= htmlspecialchars($_SESSION['username']); ?>!</li>
            <li><a href="#about">About</a></li>
            <li><a href="attendance.php">Attendance</a></li>

            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="admin_dashboard.php" class="admin-link">Admin Panel</a></li>
            <?php endif; ?>

            <li><a href="#contact" class="btn-gold">Team</a></li>
            <li><a href="gallery.php" class="btn-gold">Gallery</a></li>
            <li><a href="change_password.php">Change Password</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <header class="hero">
        <div class="hero-content">
            <h1>Smooth, <br><span>Memorable</span></h1>
            <p>We are the best jazz band at McMaster.</p>
            <a href="#shows" class="btn-main">View Leaderboard</a>
        </div>
    </header>

    <section id="about" class="section">
        <div class="container">
            <h2>Mac Eng Jazz</h2>
            <p>The most elite (and slightly chaotic) jazz band on campus.</p>
        </div>
    </section>

    <section id="shows" class="leaderboard dark-bg">
        <div class="container">
            <h2>Leader Board 👑</h2>

            <?php
            $rank = 1;

            foreach ($leaderboard as $row):

                // Get trend
                $latestPct = getEventPercentage($pdo, $row['instrument'], $latestEvent);
                $prevPct = getEventPercentage($pdo, $row['instrument'], $prevEvent);

                $trend = '→';
                $trendClass = 'trend-flat';

                if ($latestPct !== null && $prevPct !== null) {
                    if ($latestPct > $prevPct) {
                        $trend = '↑';
                        $trendClass = 'trend-up';
                    } elseif ($latestPct < $prevPct) {
                        $trend = '↓';
                        $trendClass = 'trend-down';
                    }
                }
                ?>

                <div class="board-row">

                    <!-- Rank -->
                    <span>
                        <?= match ($rank) {
                            1 => "🥇",
                            2 => "🥈",
                            3 => "🥉",
                            default => "#$rank"
                        }; ?>
                    </span>

                    <!-- Instrument -->
                    <span><?= htmlspecialchars($row['instrument']); ?></span>

                    <!-- Percentage -->
                    <span class="num-ppl">
                        <?= number_format($row['percentage'], 1); ?>%
                        <span class="<?= $trendClass ?>"><?= $trend ?></span>

                        <small>
                            <?= $row['total_attended']; ?> / <?= $row['total_records']; ?> records
                        </small>
                    </span>

                </div>

                <?php
                $rank++;
            endforeach;
            ?>

        </div>
    </section>

    <footer id="contact">
        <p>Inquiries: @MacEngJazz</p>
        <p>&copy; 2026 Mac Eng Jazz</p>
    </footer>

</body>

</html>