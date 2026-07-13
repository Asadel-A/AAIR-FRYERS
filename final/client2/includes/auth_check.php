<!-- 
Name: Roshan Azeemi
Date: Mar 13th 2026
Description: Authentication check
-->

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
