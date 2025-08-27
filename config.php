<?php
// config.php

// --- Database Connection ---
$host = 'localhost';
$db   = 'sharadhaskillnex_new_crm';
$user = 'sharadhaskillnex_new_user';
$pass = 'K6uK4c8RodP7C';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


// --- Facebook Conversions API Credentials ---

// Check if the constant is already defined before defining it.
if (!defined('FACEBOOK_PIXEL_ID')) {
    define('FACEBOOK_PIXEL_ID', '1496126924761832'); // Find this in Events Manager
}

if (!defined('FACEBOOK_ACCESS_TOKEN')) {
    define('FACEBOOK_ACCESS_TOKEN', 'EAAOiZCp5mZC8ABPLcOdiXKQ0vtybLXyPv2MrwBYQikef7FkQqaQKVRMqToZA3l6WV1TDFUusbavWctZB3xj776OYw4Wv5eQ9zksxm5dO2FYcwROR8ZC4BhDErsFiy7OZB6sezoCsq0HZCDeYpvIXX15soa4PFZBx0if8xEbeGDAiZAD3d96tR7ads1Bq70ZANdzc6zxgZDZD');
}

?>