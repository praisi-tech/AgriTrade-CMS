<?php
require_once 'auth.php';

// Log logout event
if (isLoggedIn()) {
    logSecurityEvent('LOGOUT', 'User: ' . getCurrentUsername());
}

// Use auth.php logout function
logout();

// Redirect to login page
redirectToLogin();
?>
