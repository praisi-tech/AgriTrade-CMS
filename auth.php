<?php
/**
 * AUTHENTICATION & SESSION MANAGEMENT
 * 
 * Centralized authentication system for AgriTrade CMS
 * Include this file at the top of any page that requires admin access
 * 
 * Usage:
 *   require_once 'auth.php';
 *   checkAuth(); // Redirects to login if not authenticated
 */

// ============================================
// SESSION SECURITY SETTINGS (BEFORE session_start!)
// ============================================

// Set secure session parameters BEFORE starting session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    // Only enable if using HTTPS
    // ini_set('session.cookie_secure', 1);
    
    // Start session
    session_start();
}

// ============================================
// CONFIGURATION
// ============================================

// Session timeout in seconds (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Login page URL
define('LOGIN_URL', 'login.php');

// Admin dashboard URL
define('ADMIN_URL', 'xf4-agritrade-cms.php');

// ============================================
// AUTHENTICATION FUNCTIONS
// ============================================

/**
 * Check if user is authenticated
 * Redirects to login page if not authenticated
 */
function checkAuth() {
    // Check if admin is logged in
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // Store the attempted URL to redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        header("Location: " . LOGIN_URL);
        exit;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        
        if ($inactive_time > SESSION_TIMEOUT) {
            // Session expired
            logout();
            $_SESSION['error_message'] = "Session expired. Please login again.";
            header("Location: " . LOGIN_URL);
            exit;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID periodically (every 30 minutes)
    if (!isset($_SESSION['session_created'])) {
        $_SESSION['session_created'] = time();
    } else if (time() - $_SESSION['session_created'] > SESSION_TIMEOUT) {
        session_regenerate_id(true);
        $_SESSION['session_created'] = time();
    }
}

/**
 * Check if user is authenticated (returns boolean)
 * Use this when you want to check without redirecting
 */
function isLoggedIn() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        
        if ($inactive_time > SESSION_TIMEOUT) {
            return false;
        }
    }
    
    return true;
}

/**
 * Login user and set session variables
 * 
 * @param int $user_id User ID from database
 * @param string $username Username
 */
function loginUser($user_id, $username) {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = $user_id;
    $_SESSION['admin_username'] = $username;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['session_created'] = time();
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    
    // Reset login attempts
    $_SESSION['login_attempts'] = 0;
}

/**
 * Logout user and destroy session
 */
function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Get current logged in user ID
 * 
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
}

/**
 * Get current logged in username
 * 
 * @return string|null Username or null if not logged in
 */
function getCurrentUsername() {
    return isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : null;
}

/**
 * Check if session is from same IP and User Agent
 * Helps prevent session hijacking
 * 
 * @return bool True if session appears valid
 */
function validateSession() {
    // Check if IP matches
    if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
        return false;
    }
    
    // Check if User Agent matches
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        return false;
    }
    
    return true;
}

/**
 * Enhanced authentication check with session validation
 */
function checkAuthStrict() {
    checkAuth();
    
    // Additional security: validate session
    if (!validateSession()) {
        logout();
        $_SESSION['error_message'] = "Security violation detected. Please login again.";
        header("Location: " . LOGIN_URL);
        exit;
    }
}

/**
 * Get session time remaining in seconds
 * 
 * @return int Seconds remaining before session expires
 */
function getSessionTimeRemaining() {
    if (!isset($_SESSION['last_activity'])) {
        return 0;
    }
    
    $inactive_time = time() - $_SESSION['last_activity'];
    $remaining = SESSION_TIMEOUT - $inactive_time;
    
    return max(0, $remaining);
}

/**
 * Get session time remaining in human readable format
 * 
 * @return string Time remaining (e.g., "25 minutes")
 */
function getSessionTimeRemainingFormatted() {
    $seconds = getSessionTimeRemaining();
    
    if ($seconds === 0) {
        return "Expired";
    }
    
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;
    
    if ($minutes > 0) {
        return "{$minutes} minute" . ($minutes != 1 ? 's' : '');
    } else {
        return "{$seconds} second" . ($seconds != 1 ? 's' : '');
    }
}

// ============================================
// CSRF TOKEN FUNCTIONS
// ============================================

/**
 * Generate CSRF token
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require CSRF token (dies if invalid)
 * Use in forms that modify data
 * 
 * @param string $token Token from POST request
 */
function requireCSRFToken($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    
    if (!verifyCSRFToken($token)) {
        http_response_code(403);
        die("CSRF token validation failed. Please refresh the page and try again.");
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Redirect to admin dashboard
 */
function redirectToAdmin() {
    header("Location: " . ADMIN_URL);
    exit;
}

/**
 * Redirect to login page
 */
function redirectToLogin() {
    header("Location: " . LOGIN_URL);
    exit;
}

/**
 * Set success message
 * 
 * @param string $message Success message
 */
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Set error message
 * 
 * @param string $message Error message
 */
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Get and clear success message
 * 
 * @return string|null Message or null
 */
function getSuccessMessage() {
    $message = $_SESSION['success_message'] ?? null;
    unset($_SESSION['success_message']);
    return $message;
}

/**
 * Get and clear error message
 * 
 * @return string|null Message or null
 */
function getErrorMessage() {
    $message = $_SESSION['error_message'] ?? null;
    unset($_SESSION['error_message']);
    return $message;
}

/**
 * Log security event
 * 
 * @param string $event Event name
 * @param string $details Event details
 */
function logSecurityEvent($event, $details = '') {
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    $username = getCurrentUsername() ?? 'anonymous';
    
    $log_message = "[{$timestamp}] {$event} - User: {$username} - IP: {$ip}";
    
    if (!empty($details)) {
        $log_message .= " - {$details}";
    }
    
    $log_message .= "\n";
    
    // Create logs directory if it doesn't exist
    if (!file_exists('logs')) {
        mkdir('logs', 0755, true);
    }
    
    error_log($log_message, 3, 'logs/security.log');
}

// ============================================
// AUTO-EXECUTION
// ============================================

// Log session start for monitoring
if (!isset($_SESSION['session_logged'])) {
    if (isLoggedIn()) {
        logSecurityEvent('SESSION_ACTIVE', 'User: ' . getCurrentUsername());
        $_SESSION['session_logged'] = true;
    }
}
?>
