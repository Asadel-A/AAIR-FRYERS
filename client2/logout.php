<!--
Name: Roshan Azeemi
Date: March 16 2026
Description: Logs the user out of the website and destroys their link to the login.
-->


<?php
session_start();
session_destroy();
header("Location: member_login.php");
exit();
?>
