<?php
require_once 'includes/functions.php';
startSession();
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
} else {
    header('Location: ' . BASE_URL . 'login.php');
}
exit;
