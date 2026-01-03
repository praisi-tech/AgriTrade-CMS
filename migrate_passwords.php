<?php
/**
 * PASSWORD MIGRATION SCRIPT
 * 
 * This script migrates plain text passwords to secure bcrypt hashes.
 * Run this ONCE after deploying the secure login.php
 * 
 * IMPORTANT: Backup your database before running!
 * Command: mysqldump -u root export_db > backup_before_migration.sql
 */

include 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Password Migration</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #00ff00; }
        .success { color: #00ff00; }
        .warning { color: #ffaa00; }
        .error { color: #ff0000; }
        .info { color: #00aaff; }
        pre { background: #000; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
<h2>🔐 Password Migration Tool</h2>
<pre>";

// Check if running from command line or browser
$is_cli = (php_sapi_name() === 'cli');

// Security check - only allow from localhost in browser
if (!$is_cli && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die("<span class='error'>❌ ERROR: This script can only be run from localhost for security reasons.</span></pre></body></html>");
}

echo "<span class='info'>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</span>\n";
echo "<span class='info'>  PASSWORD MIGRATION STARTED</span>\n";
echo "<span class='info'>  Time: " . date('Y-m-d H:i:s') . "</span>\n";
echo "<span class='info'>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</span>\n\n";

$result = mysqli_query($conn, "SELECT id, username, password FROM users");

if (!$result) {
    echo "<span class='error'>❌ ERROR: Could not fetch users from database</span>\n";
    echo "<span class='error'>   " . mysqli_error($conn) . "</span>\n";
    exit;
}

$total_users = mysqli_num_rows($result);
$migrated = 0;
$already_hashed = 0;
$failed = 0;

echo "<span class='info'>📊 Found {$total_users} user(s) in database</span>\n\n";

while($row = mysqli_fetch_assoc($result)) {
    $user_id = $row['id'];
    $username = $row['username'];
    $current_password = $row['password'];
    
    // Check if password is already hashed (bcrypt hashes are 60 characters and start with $2y$)
    if (strlen($current_password) === 60 && substr($current_password, 0, 4) === '$2y$') {
        echo "<span class='warning'>⊘ SKIP: User '{$username}' (ID: {$user_id}) - Already hashed</span>\n";
        $already_hashed++;
        continue;
    }
    
    // Hash the plain text password
    $hashed_password = password_hash($current_password, PASSWORD_DEFAULT);
    
    // Update database
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    
    if ($stmt->execute()) {
        echo "<span class='success'>✓ SUCCESS: User '{$username}' (ID: {$user_id}) - Password hashed</span>\n";
        $migrated++;
    } else {
        echo "<span class='error'>✗ FAILED: User '{$username}' (ID: {$user_id}) - " . $stmt->error . "</span>\n";
        $failed++;
    }
    
    $stmt->close();
}

echo "\n<span class='info'>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</span>\n";
echo "<span class='info'>  MIGRATION SUMMARY</span>\n";
echo "<span class='info'>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</span>\n";
echo "  Total Users:        {$total_users}\n";
echo "  <span class='success'>Migrated:           {$migrated}</span>\n";
echo "  <span class='warning'>Already Hashed:     {$already_hashed}</span>\n";
echo "  <span class='error'>Failed:             {$failed}</span>\n";
echo "<span class='info'>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</span>\n\n";

if ($migrated > 0) {
    echo "<span class='success'>✓ Migration completed successfully!</span>\n\n";
    echo "<span class='warning'>⚠️  NEXT STEPS:</span>\n";
    echo "   1. Test login with existing credentials\n";
    echo "   2. Update login.php line 75:\n";
    echo "      Change: if (\$password === \$user['password']) {\n";
    echo "      To:     if (password_verify(\$password, \$user['password'])) {\n";
    echo "   3. Delete this migration script for security:\n";
    echo "      rm migrate_passwords.php\n\n";
} else if ($already_hashed === $total_users) {
    echo "<span class='success'>✓ All passwords are already hashed. No migration needed.</span>\n";
} else {
    echo "<span class='error'>⚠️  Some passwords failed to migrate. Check errors above.</span>\n";
}

echo "</pre></body></html>";

mysqli_close($conn);
?>
