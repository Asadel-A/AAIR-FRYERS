<!-- 
Name: Roshan & Asadel
Date: April 19 2026
Description: Allows any logged-in user (member or admin) to update their password.

-->

<?php


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
    <link rel="stylesheet" href="css/cp.css">
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
