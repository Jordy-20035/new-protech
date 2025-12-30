<?php
session_start();

// =====================================================
// DATABASE CONFIGURATION - PostgreSQL
// =====================================================
// 
// INSTRUCTIONS:
// 1. Install PostgreSQL if not already installed
// 2. Create a database named 'protech_db'
// 3. Update the credentials below to match your setup
// 
// =====================================================

// Database connection settings
define('DB_HOST', 'localhost');      // Usually 'localhost' or '127.0.0.1'
define('DB_PORT', '5432');           // Default PostgreSQL port is 5432
define('DB_NAME', 'protech_db');     // Database name
define('DB_USER', 'postgres');       // Your PostgreSQL username (default is 'postgres')
define('DB_PASS', 'postgres123');               // Your PostgreSQL password (set during installation)

// =====================================================
// HOW TO FIND/SET YOUR POSTGRESQL PASSWORD:
// 
// Windows:
//   - If you just installed PostgreSQL, the password was set during installation
//   - If you forgot it, you can reset it via pgAdmin or psql
// 
// To reset password via psql:
//   1. Open Command Prompt as Administrator
//   2. Run: psql -U postgres
//   3. Run: ALTER USER postgres PASSWORD 'your_new_password';
//   4. Update DB_PASS above with your new password
// =====================================================

// Build connection string for PDO
$dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;

try {
    // Create PDO connection
    $conn = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Set client encoding to UTF-8
    $conn->exec("SET NAMES 'UTF8'");
    
} catch (PDOException $e) {
    // Show helpful error message during development
    $error_message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ef4444; border-radius: 8px; background: #fee2e2;'>
        <h2 style='color: #991b1b; margin-top: 0;'>Database Connection Failed</h2>
        <p style='color: #991b1b;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <hr style='border-color: #fecaca;'>
        <h3 style='color: #991b1b;'>Troubleshooting Steps:</h3>
        <ol style='color: #7f1d1d;'>
            <li>Make sure PostgreSQL is installed and running</li>
            <li>Verify the database '<strong>protech_db</strong>' exists</li>
            <li>Check your credentials in <code>config.php</code>:
                <ul>
                    <li>DB_HOST: " . DB_HOST . "</li>
                    <li>DB_PORT: " . DB_PORT . "</li>
                    <li>DB_NAME: " . DB_NAME . "</li>
                    <li>DB_USER: " . DB_USER . "</li>
                </ul>
            </li>
            <li>Make sure the PHP pdo_pgsql extension is enabled</li>
        </ol>
        <h3 style='color: #991b1b;'>Quick Commands:</h3>
        <pre style='background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 6px; overflow-x: auto;'>
# Create the database (run in psql or pgAdmin):
CREATE DATABASE protech_db;

# Then import the schema:
psql -U postgres -d protech_db -f database.sql
        </pre>
    </div>";
    die($error_message);
}
?>
