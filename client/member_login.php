<?php
require 'config/db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_input = $_POST['username'];
    $pass_input = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'member'");
    $stmt->execute([$user_input]);
    $user = $stmt->fetch();

    if ($user && $pass_input === $user['password']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Member login failed.";
    }
}
?>
<link rel="stylesheet" href="style.css">
<form method="POST">
    <h2>Jazz Club Member Login</h2>
    <?php if (isset($error))
        echo "<p style='color:red'>$error</p>"; ?>
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>


    <div class="login-footer" style="margin-top: 20px; font-size: 0.8rem;">
        <p>Not a member? <a href="register.php">Create an account</a></p>
        <hr>
        <p>Are you an Admin? <a href="admin_login.php" style="color: #ffcc00; font-weight: bold;">Click here to
                login</a></p>
    </div>
</form>