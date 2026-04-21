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
<html>

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

        /* Create Account Form */
        .create-account-section {
            margin-top: 40px;
            background: #1a1a1c;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 30px;
        }

        .create-account-section h2 {
            margin-bottom: 20px;
            font-size: 1.4rem;
        }

        .create-account-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 0.75rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-group input,
        .form-group select {
            padding: 10px 14px;
            background: #0d0d0d;
            border: 1px solid #444;
            border-radius: 6px;
            color: #e0d9d1;
            font-size: 0.9rem;
            font-family: 'Montserrat', sans-serif;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #d4af37;
        }

        .form-group input::placeholder {
            color: #555;
        }

        .btn-create {
            padding: 10px 24px;
            background: #d4af37;
            color: #0a0a0b;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.9rem;
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            height: fit-content;
            align-self: end;
        }

        .btn-create:hover {
            background: #e6c34a;
            transform: translateY(-1px);
        }

        .btn-create:active {
            transform: translateY(0);
        }

        .flash-msg {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .flash-msg.success {
            background: rgba(76, 175, 80, 0.15);
            border: 1px solid #4caf50;
            color: #81c784;
        }

        .flash-msg.error {
            background: rgba(244, 67, 54, 0.15);
            border: 1px solid #f44336;
            color: #e57373;
        }
    </style>
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
        <p style="color:#888">Welcome back,
            <?php echo htmlspecialchars($_SESSION['username']); ?>.
        </p>

        <div class="admin-grid">
            <div class="stat-card">
                <div class="num">
                    <?php echo (int) $totalMembers; ?>
                </div>
                <div class="label">Band Members</div>
            </div>
            <div class="stat-card">
                <div class="num">
                    <?php echo (int) $totalEvents; ?>
                </div>
                <div class="label">Events Tracked</div>
            </div>
            <div class="stat-card">
                <div class="num">
                    <?php echo (int) $totalRecords; ?>
                </div>
                <div class="label">Attendance Records</div>
            </div>
        </div>

        <!-- Create Account Section -->
        <div class="create-account-section">
            <h2>Create New Account</h2>
            <form action="admin_actions.php" method="POST" class="create-account-form" id="create-account-form">
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
                <button type="submit" class="btn-create">Create</button>
            </form>
        </div>

        <h2 style="margin-top:30px;">Actions</h2>
        <div class="action-cards">
            <a href="import_attendance.php" class="action-card">
                <h3> Import Attendance</h3>
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
                <h3> Back to Home</h3>
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
            <p class="last-import">Last import:
                <?php echo date('F j, Y \a\t g:i A', strtotime($lastImport)); ?>
            </p>
        <?php endif; ?>
    </div>
</body>

</html>