<?php
require_once 'includes/functions.php';
startSession();
logAktivitas('Logout', 'User logout');
session_destroy();
header('Location: ' . BASE_URL . 'login.php');
exit;
