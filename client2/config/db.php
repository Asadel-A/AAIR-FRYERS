<!-- 
Name: Roshan Azeemi
Date: Mar 30th 2026
Description: This file is included in other files as it is needed to connect to the database. 
-->

<?php
$host = "localhost";
$dbname = "azeemir_db"; 
$username = "azeemir_local";
$password = "{+IRXCT9";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

}
catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
