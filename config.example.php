<?php
/**
 * File: config.php.example
 * Instruction: Copy this file to 'config.php' and fill in your database credentials.
 */

$host = "localhost";
$user = "your_username";
$pass = "your_password";
$dbname = "your_database_name";

// Create connection with error suppression for security
$conn = @mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    // Log error securely (don't expose to users)
    error_log("Database connection failed: " . mysqli_connect_error());
    
    // Show generic error to users (don't expose system details)
    die("
    <!DOCTYPE html>
    <html>
    <head>
        <title>Service Unavailable</title>
        <style>
            body { font-family: Arial; text-align: center; padding: 50px; background: #f5f5f5; }
            .error-box { background: white; padding: 30px; border-radius: 10px; 
                         box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 500px; margin: 0 auto; }
            h1 { color: #dc3545; }
        </style>
    </head>
    <body>
        <div class='error-box'>
            <h1>Service Temporarily Unavailable</h1>
            <p>We're experiencing technical difficulties. Please try again later.</p>
            <p><a href='index.php'>← Return to Home</a></p>
        </div>
    </body>
    </html>
    ");
}

// Set charset to UTF-8 for security
mysqli_set_charset($conn, "utf8mb4");

// Disable error display in production (uncomment in production)
// mysqli_report(MYSQLI_REPORT_OFF);

// Optional: Set timezone
date_default_timezone_set('Asia/Jakarta');
?>
