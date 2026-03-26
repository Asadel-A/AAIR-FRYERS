<?php
include 'includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php"); // Kick non-admins out
    exit();
}
?>
<h1>Admin Control Panel</h1>
<p>Welcome, Niko. Use this page to upload Excel data or modify attendance.</p>
<a href="index.php">Back to Home</a>