<?php
/**
 * admin_actions.php
 * Handles admin POST actions (e.g. creating user accounts).
 * All actions require an active admin session.
 */

include 'includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require 'config/db.php';

// ──────────────────────────────────────────────
// Action: create_user
// Creates a new user account with a random
// temporary password and emails it to the user.
// ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = trim($_POST['role'] ?? 'member');

    // --- Basic validation ---
    if ($username === '' || $email === '') {
        header("Location: admin_dashboard.php?error=" . urlencode("Username and email are required."));
        exit();
    }

    // Whitelist role values
    if (!in_array($role, ['member', 'admin'], true)) {
        $role = 'member';
    }

    // Check if username already exists
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->rowCount() > 0) {
        header("Location: admin_dashboard.php?error=" . urlencode("Username \"$username\" is already taken."));
        exit();
    }

    // Check if email already exists
    $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->execute([$email]);
    if ($checkEmail->rowCount() > 0) {
        header("Location: admin_dashboard.php?error=" . urlencode("An account with that email already exists."));
        exit();
    }

    // --- Generate a secure random 8-character temporary password ---
    $tempPassword = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$'), 0, 8);
    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

    // --- INSERT into the users table ---
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword, $role]);
    } catch (PDOException $e) {
        header("Location: admin_dashboard.php?error=" . urlencode("Database error: " . $e->getMessage()));
        exit();
    }

    // --- Redirect with feedback (share temp password with admin to relay manually) ---
    header("Location: admin_dashboard.php?success=" . urlencode("Account created for \"$username\". Temp password: $tempPassword"));
    exit();
}

// If no valid action matched, redirect back
header("Location: admin_dashboard.php");
exit();
?>
