<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset();
session_destroy();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['toast'] = ['type' => 'info', 'message' => 'You have been logged out.'];
header("Location: index.php");
exit;
?>
