<?php
// config.php

// Display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection parameters
define('DB_HOST', 'mysql');
define('DB_PORT', '3306');
define('DB_NAME', 'aics_tms');              // Replace with your database name
define('DB_USER', 'aics_user');             // Replace with your database user
define('DB_PASSWORD', 'aics_password');     // Replace with your database password
