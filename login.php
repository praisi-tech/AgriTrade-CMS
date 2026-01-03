<?php
require_once 'config.php';
require_once 'auth.php';

// If already logged in, redirect to admin
if (isLoggedIn()) {
    redirectToAdmin();
}

// ============================================
// SECURITY FUNCTIONS
// ============================================

// Rate Limiting - Prevent Brute Force
function checkRateLimit() {
    $max_attempts = 5; // Maximum login attempts
    $lockout_time = 900; // 15 minutes lockout
    
    // Initialize session variables
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = time();
    }
    
    // Check if locked out
    if ($_SESSION['login_attempts'] >= $max_attempts) {
        $time_passed = time() - $_SESSION['last_attempt_time'];
        
        if ($time_passed < $lockout_time) {
            $remaining = ceil(($lockout_time - $time_passed) / 60);
            return [
                'allowed' => false,
                'message' => "Too many failed attempts. Please try again in {$remaining} minutes."
            ];
        } else {
            // Reset after lockout period
            $_SESSION['login_attempts'] = 0;
        }
    }
    
    return ['allowed' => true];
}

// Log failed login attempt
function logFailedLogin($username) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $timestamp = date('Y-m-d H:i:s');
    
    $log_message = "Failed login attempt - Username: {$username} - IP: {$ip} - Time: {$timestamp}\n";
    error_log($log_message, 3, 'logs/failed_logins.log');
}

// ============================================
// HANDLE LOGIN
// ============================================
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        // Check rate limit
        $rate_limit = checkRateLimit();
        
        if (!$rate_limit['allowed']) {
            $error = $rate_limit['message'];
        } else {
            // Sanitize input
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            
            // Validate input
            if (empty($username) || empty($password)) {
                $error = "Please enter both username and password.";
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
            } else {
                // Use prepared statement
                $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Verify password
                    // If using password_hash (RECOMMENDED):
                    // if (password_verify($password, $user['password'])) {
                    
                    // Current plain text comparison (INSECURE - see migration guide below):
                    if ($password === $user['password']) {
                        // Success - Use auth.php loginUser function
                        loginUser($user['id'], $user['username']);
                        
                        // Log successful login
                        logSecurityEvent('LOGIN_SUCCESS', 'User: ' . $user['username']);
                        
                        // Redirect to intended page or admin dashboard
                        $redirect = $_SESSION['redirect_after_login'] ?? 'xf4-agritrade-cms.php';
                        unset($_SESSION['redirect_after_login']);
                        
                        $stmt->close();
                        header("Location: " . $redirect);
                        exit;
                    } else {
                        // Wrong password
                        $_SESSION['login_attempts']++;
                        $_SESSION['last_attempt_time'] = time();
                        $error = "Invalid username or password.";
                        logFailedLogin($username);
                    }
                } else {
                    // User not found
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt_time'] = time();
                    $error = "Invalid username or password.";
                    logFailedLogin($username);
                }
                $stmt->close();
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | AgriTrade CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login-style.css">
</head>
<body>

    <a href="index.php" class="brand-logo">AgriTrade CMS</a>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card login-card p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h2 class="card-header-title fw-bold mb-1">Export Portal</h2>
                        <p class="text-muted small">Please sign in to manage commodities</p>
                    </div>

                    <?php if($error): ?>
                        <div class="alert alert-danger d-flex align-items-center small" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle-fill me-2" viewBox="0 0 16 16">
                                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                            </svg>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] > 0): ?>
                        <div class="alert alert-warning small" role="alert">
                            <strong>Warning:</strong> <?php echo $_SESSION['login_attempts']; ?> failed attempt(s). 
                            <?php echo (5 - $_SESSION['login_attempts']); ?> remaining before lockout.
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Username</label>
                            <input type="text" 
                                   name="username" 
                                   class="form-control" 
                                   placeholder="Enter username" 
                                   required 
                                   autofocus
                                   maxlength="50"
                                   autocomplete="username">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Password</label>
                            <input type="password" 
                                   name="password" 
                                   class="form-control" 
                                   placeholder="Enter password" 
                                   required
                                   maxlength="255"
                                   autocomplete="current-password">
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100 mb-3">
                            Sign In to Dashboard
                        </button>
                    </form>

                    <div class="text-center">
                        <a href="index.php" class="text-decoration-none small text-muted hover-link">
                            ← Back to Public Website
                        </a>
                    </div>
                </div>
                <p class="text-center text-white-50 mt-4 small">
                    &copy; 2025 AgriTrade CMS Admin System
                </p>
            </div>
        </div>
    </div>

</body>
</html>
