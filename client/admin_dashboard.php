<?php
include 'includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
require 'config/db.php';

$totalMembers = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$totalEvents = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalRecords = $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
$lastImport = $pdo->query("SELECT imported_at FROM import_log ORDER BY imported_at DESC LIMIT 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | MEJ</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: #1a1a1c;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .stat-card .num {
            font-size: 2rem;
            color: #d4af37;
            font-weight: bold;
        }

        .stat-card .label {
            font-size: 0.8rem;
            color: #888;
            margin-top: 5px;
        }

        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .action-card {
            background: #1a1a1c;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 25px;
            text-decoration: none;
            color: #e0d9d1;
            transition: border-color 0.2s;
        }

        .action-card:hover {
            border-color: #d4af37;
        }

        .action-card h3 {
            color: #d4af37;
            margin-bottom: 8px;
        }

        .action-card p {
            font-size: 0.85rem;
            color: #888;
        }

        .welcome {
            color: #d4af37;
            margin-bottom: 5px;
        }

        .last-import {
            font-size: 0.8rem;
            color: #666;
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <div class="container" style="padding-top: 40px;">
        <h1 class="welcome">Admin Control Panel</h1>
        <p style="color:#888">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>.</p>

        <div class="admin-grid">
            <div class="stat-card">
                <div class="num"><?php echo (int) $totalMembers; ?></div>
                <div class="label">Band Members</div>
            </div>
            <div class="stat-card">
                <div class="num"><?php echo (int) $totalEvents; ?></div>
                <div class="label">Events Tracked</div>
            </div>
            <div class="stat-card">
                <div class="num"><?php echo (int) $totalRecords; ?></div>
                <div class="label">Attendance Records</div>
            </div>
        </div>

        <h2 style="margin-top:30px;">Actions</h2>
        <div class="action-cards">
            <a href="import_attendance.php" class="action-card">
                <h3>📥 Import Attendance</h3>
                <p>Upload an Excel (.xlsx) file to sync attendance data into the database.</p>
            </a>
            <a href="manage_members.php" class="action-card">
                <h3>👥 Manage Members</h3>
                <p>Add, edit, or remove band members from the database.</p>
            </a>
            <a href="manage_events.php" class="action-card">
                <h3>📅 Manage Events</h3>
                <p>Add, edit, or remove events and practices.</p>
            </a>
            <a href="manage_attendance.php" class="action-card">
                <h3>📋 Manage Attendance</h3>
                <p>Manually add, update, or delete attendance records.</p>
            </a>
            <a href="index.php" class="action-card">
                <h3>🏠 Back to Home</h3>
                <p>Return to the main page.</p>
            </a>
            <a href="logout.php" class="action-card">
                <h3>🚪 Logout</h3>
                <p>End Admin Session.</p>
            </a>
        </div>

        <?php if ($lastImport): ?>
            <p class="last-import">Last import: <?php echo date('F j, Y \a\t g:i A', strtotime($lastImport)); ?></p>
        <?php endif; ?>
    </div>
</body>

</html>