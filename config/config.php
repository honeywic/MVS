<?php
$host = '127.0.0.1';     
$db   = 'mvs';           
$user = 'root';          
$pass = '';             
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
    PDO::ATTR_EMULATE_PREPARES   => false,                   
    PDO::ATTR_PERSISTENT         => true, // Persistent connection for LAN
];

// Security: Restrict access to localhost only
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied. This system is only available on the local network.');
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Security: Set strict SQL mode
    $pdo->exec("SET SESSION sql_mode='STRICT_ALL_TABLES'");
    // Security: Set timezone for consistency
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    error_log('Database Connection Error: ' . $e->getMessage(), 3, __DIR__ . '/error.log');
    die('Database connection failed. Please contact the administrator.');
}

// Security: Prevent direct access to config file
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    die('Access denied.');
}
?>
