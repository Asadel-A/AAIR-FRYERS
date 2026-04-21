<?php

include 'includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
require 'config/db.php';

$totalMembers = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$totalEvents  = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalRecords = $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
$lastImport   = $pdo->query("SELECT imported_at FROM import_log ORDER BY imported_at DESC LIMIT 1")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | MEJ</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="container" style="padding-top: 40px;">

    <?php if (isset($_GET['success'])): ?>
        <div class="flash-msg success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="flash-msg error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <h1 class="welcome">Admin Control Panel</h1>
    <p style="color: #888;">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>.</p>

    <div class="admin-grid">
        <div class="stat-card">
            <div class="num"><?php echo (int)$totalMembers; ?></div>
            <div class="label">Band Members</div>
        </div>
        <div class="stat-card">
            <div class="num"><?php echo (int)$totalEvents; ?></div>
            <div class="label">Events Tracked</div>
        </div>
        <div class="stat-card">
            <div class="num"><?php echo (int)$totalRecords; ?></div>
            <div class="label">Attendance Records</div>
        </div>
    </div>

    <div class="create-account-section">
        <h2>Create New Account</h2>
        <form action="admin_actions.php" method="POST" class="create-account-form">
            <input type="hidden" name="action" value="create_user">
            <div class="form-group">
                <label for="new-username">Username</label>
                <input type="text" id="new-username" name="username" placeholder="e.g. jsmith" required>
            </div>
            <div class="form-group">
                <label for="new-email">Email</label>
                <input type="email" id="new-email" name="email" placeholder="user@example.com" required>
            </div>
            <div class="form-group">
                <label for="new-role">Role</label>
                <select id="new-role" name="role" required>
                    <option value="member">Member</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn-create">Create &amp; Email</button>
        </form>
    </div>

    <h2 style="margin-top: 30px;">Actions</h2>
    <div class="action-cards">
        <a href="import_attendance.php" class="action-card">
            <h3>Import Attendance</h3>
            <p>Upload an Excel (.xlsx) file to sync attendance data into the database.</p>
        </a>
        <a href="manage_members.php" class="action-card">
            <h3> Manage Members</h3>
            <p>Add, edit, or remove band members from the database.</p>
        </a>
        <a href="manage_events.php" class="action-card">
            <h3> Manage Events</h3>
            <p>Add, edit, or remove events and practices.</p>
        </a>
        <a href="manage_attendance.php" class="action-card">
            <h3> Manage Attendance</h3>
            <p>Manually add, update, or delete attendance records.</p>
        </a>
        <a href="index.php" class="action-card">
            <h3>Back to Home</h3>
            <p>Return to the main page.</p>
        </a>
        <a href="change_password.php" class="action-card">
            <h3> Change Password</h3>
            <p>Update your account password.</p>
        </a>
        <a href="logout.php" class="action-card">
            <h3> Logout</h3>
            <p>End Admin Session.</p>
        </a>
    </div>

    <?php if ($lastImport): ?>
        <p class="last-import">
            Last import: <?php echo date('F j, Y \a\t g:i A', strtotime($lastImport)); ?>
        </p>
    <?php endif; ?>

</div>
</body>
</html>
