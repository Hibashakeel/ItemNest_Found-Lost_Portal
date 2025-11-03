<?php
session_start();

// Define base URL and root path
define('BASE_URL', 'http://localhost/ItemNest/');
define('ROOT_PATH', dirname(__FILE__) . '/');

// Auto-load classes
spl_autoload_register(function ($class_name) {
    $file = ROOT_PATH . 'classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
?>