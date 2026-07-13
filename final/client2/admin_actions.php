<?php
/**
 * Name: Roshan Azeemi
 * Date: April 5th 2026
 * Description: This file is created to allow an admin to create new user accounts; the information is 
 provided and submitted within the admin dashboard. This file also creates a randomly generated 8-character password.
 */

include 'includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = trim($_POST['role'] ?? 'member');

    if ($username === '' || $email === '') {
        header("Location: admin_dashboard.php?error=" . urlencode("Username and email are required."));
        exit();
    }

    if (!in_array($role, ['member', 'admin'], true)) {
        $role = 'member';
    }

    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->rowCount() > 0) {
        header("Location: admin_dashboard.php?error=" . urlencode("Username \"$username\" is already taken."));
        exit();
    }

    $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->execute([$email]);
    if ($checkEmail->rowCount() > 0) {
        header("Location: admin_dashboard.php?error=" . urlencode("An account with that email already exists."));
        exit();
    }

    // generates a random secure password
    $tempPassword = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$'), 0, 8);
    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword, $role]);
    } catch (PDOException $e) {
        header("Location: admin_dashboard.php?error=" . urlencode("Database error: " . $e->getMessage()));
        exit();
    }

    header("Location: admin_dashboard.php?success=" . urlencode("Account created for \"$username\". Temp password: $tempPassword"));
    exit();
}

// If no valid action matched, redirect back
header("Location: admin_dashboard.php");
exit();
?>
