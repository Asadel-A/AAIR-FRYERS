<?php
// On Mac CAS servers, 'localhost' usually works, but sometimes it needs an IP
$host = "localhost";
$dbname = "azeemir_db"; // From your previous screenshot
$username = "azeemir_local";

// --- IMPORTANT ---
// You MUST put your actual database password here. 
// If you don't know it, check your Mac CAS welcome email or Avenue.
$password = "{+IRXCT9";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Success! (You can comment this out once it works)
// echo "Connected successfully"; 
}
catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>