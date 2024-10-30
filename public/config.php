<?php
// config.php

// Display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection parameters
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'tms');              // Replace with your database name
define('DB_USER', 'root');             // Replace with your database user
define('DB_PASSWORD', '');     // Replace with your database password

