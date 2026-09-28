<?php
// logout.php - Sign Out Handler
require_once __DIR__ . '/includes/functions.php';

session_unset();
session_destroy();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

setFlash('info', 'You have been signed out successfully.');
header('Location: index.php');
exit;
