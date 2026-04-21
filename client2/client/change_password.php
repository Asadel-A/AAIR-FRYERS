<?php
/**
 * change_password.php
 * Allows any logged-in user (member or admin) to update their password.
 */

include 'includes/auth_check.php';
require 'config/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmNewPassword = $_POST['confirm_new_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "User account not found.";
    } elseif (!password_verify($currentPassword, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif ($newPassword === '') {
        $error = "New password cannot be empty.";
    } elseif (strlen($newPassword) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($newPassword !== $confirmNewPassword) {
        $error = "New passwords do not match.";
    } else {

        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$hashedPassword, $_SESSION['user_id']]);
            $success = "Password updated successfully!";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$backLink = ($_SESSION['role'] === 'admin') ? 'admin_dashboard.php' : 'index.php';
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | MEJ</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .cp-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .cp-card {
            background: #1a1a1c;
            border: 1px solid #333;
            border-radius: 10px;
            padding: 40px 36px;
            width: 100%;
            max-width: 440px;
        }

        .cp-card h2 {
            margin-bottom: 6px;
            font-size: 1.5rem;
        }

        .cp-subtitle {
            font-size: 0.8rem;
            color: #888;
            margin-bottom: 28px;
        }

        .cp-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .cp-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .cp-group label {
            font-size: 0.75rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cp-group input {
            padding: 11px 14px;
            background: #0d0d0d;
            border: 1px solid #444;
            border-radius: 6px;
            color: #e0d9d1;
            font-size: 0.9rem;
            font-family: 'Montserrat', sans-serif;
            outline: none;
            transition: border-color 0.2s;
        }

        .cp-group input:focus {
            border-color: #d4af37;
        }

        .cp-group input::placeholder {
            color: #555;
        }

        .cp-actions {
            display: flex;
            gap: 12px;
            margin-top: 6px;
        }

        .btn-save {
            flex: 1;
            padding: 11px 20px;
            background: #d4af37;
            color: #0a0a0b;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.9rem;
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-save:hover {
            background: #e6c34a;
            transform: translateY(-1px);
        }

        .btn-save:active {
            transform: translateY(0);
        }

        .btn-back {
            padding: 11px 20px;
            background: transparent;
            border: 1px solid #555;
            border-radius: 6px;
            color: #aaa;
            font-size: 0.9rem;
            font-family: 'Montserrat', sans-serif;
            text-decoration: none;
            text-align: center;
            transition: border-color 0.2s, color 0.2s;
        }

        .btn-back:hover {
            border-color: #d4af37;
            color: #d4af37;
        }

        /* Flash messages */
        .cp-flash {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.85rem;
        }

        .cp-flash.success {
            background: rgba(76, 175, 80, 0.15);
            border: 1px solid #4caf50;
            color: #81c784;
        }

        .cp-flash.error {
            background: rgba(244, 67, 54, 0.15);
            border: 1px solid #f44336;
            color: #e57373;
        }
    </style>
</head>

<body>
    <div class="cp-wrapper">
        <div class="cp-card">

            <?php if ($success): ?>
                <div class="cp-flash success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="cp-flash error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <h2>Change Password</h2>
            <p class="cp-subtitle">Logged in as <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            </p>

            <form method="POST" class="cp-form" id="change-password-form">
                <div class="cp-group">
                    <label for="current-password">Current Password</label>
                    <input type="password" id="current-password" name="current_password"
                        placeholder="Enter current password" required>
                </div>
                <div class="cp-group">
                    <label for="new-password">New Password</label>
                    <input type="password" id="new-password" name="new_password" placeholder="Min. 6 characters"
                        required minlength="6">
                </div>
                <div class="cp-group">
                    <label for="confirm-new-password">Confirm New Password</label>
                    <input type="password" id="confirm-new-password" name="confirm_new_password"
                        placeholder="Re-enter new password" required minlength="6">
                </div>
                <div class="cp-actions">
                    <a href="<?php echo $backLink; ?>" class="btn-back">← Back</a>
                    <button type="submit" class="btn-save">Update Password</button>
                </div>
            </form>

        </div>
    </div>
</body>

</html>