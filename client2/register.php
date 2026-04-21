<?php
require 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Trim the username so people don't accidentally register "jsmith " instead of "jsmith"
    $new_user = trim($_POST['username']);
    $raw_pass = $_POST['password'];
    $role = 'member';

    // THE UPGRADE: Hash the password securely before it touches the database
    $hashed_pass = password_hash($raw_pass, PASSWORD_DEFAULT);

    $check = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $check->execute([$new_user]);

    if ($check->rowCount() > 0) {
        $error = "Username already taken!";
    } else {
        // Insert the HASHED password, not the raw password
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");

        if ($stmt->execute([$new_user, $hashed_pass, $role])) {
            $success = "Account created! You can now <a href='member_login.php'>login here</a>.";
        } else {
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