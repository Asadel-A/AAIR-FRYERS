<?php
require 'config/db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_input = $_POST['username'];
    $pass_input = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
    $stmt->execute([$user_input]);
    $user = $stmt->fetch();

    if ($user && $pass_input === $user['password']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Admin access denied or incorrect credentials.";
    }
}
?>
<link rel="stylesheet" href="style.css">
<form method="POST">
    <h2>Niko's Admin Login</h2>
    <?php if (isset($error))
        echo "<p style='color:red'>$error</p>"; ?>
    <input type="text" name="username" placeholder="Admin Username" required>
    <input type="password" name="password" placeholder="Admin Password" required>
    <button type="submit">Admin Login</button>

    <div class="login-footer" style="margin-top: 20px; font-size: 0.8rem;">
        <p>Regular member? <a href="member_login.php">Go to Member Login</a></p>
    </div>
</form>