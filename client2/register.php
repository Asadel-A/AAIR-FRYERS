<?php
require 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_user = $_POST['username'];
    $new_pass = $_POST['password'];
    $role = 'member'; // New sign-ups are always members by default

    // Check if username already exists
    $check = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $check->execute([$new_user]);

    if ($check->rowCount() > 0) {
        $error = "Username already taken!";
    }
    else {
        // Insert the new user into the database
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        if ($stmt->execute([$new_user, $new_pass, $role])) {
            $success = "Account created! You can now <a href='member_login.php'>login here</a>.";
        }
        else {
            $error = "Something went wrong.";
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="style.css">
    <title>Join the Jazz Club</title>
</head>

<body>
    <form method="POST">
        <h2>Create an Account</h2>
        <?php
if (isset($error))
    echo "<p style='color:red'>$error</p>";
if (isset($success))
    echo "<p style='color:green'>$success</p>";
?>
        <input type="text" name="username" placeholder="Choose a Username" required>
        <input type="password" name="password" placeholder="Choose a Password" required>
        <button type="submit">Sign Up</button>
        <p>Already have an account? <a href="member_login.php">Login here</a></p>
    </form>
</body>

</html>