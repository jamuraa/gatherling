<?php
session_start();
unset($_SESSION['sessionname']);
unset($_SESSION['username']);
session_destroy();
header("location: index.php");
?>
