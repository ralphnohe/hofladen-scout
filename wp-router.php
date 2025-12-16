<?php
/**
 * WordPress Router for PHP Built-in Server
 * Usage: php -S localhost:8001 wp-router.php
 */

$root = $_SERVER['DOCUMENT_ROOT'];
$path = '/' . ltrim(parse_url($_SERVER['REQUEST_URI'])['path'], '/');

// Serve existing files directly
if ($path !== '/' && file_exists($root . $path)) {
    // Check if it's a PHP file
    if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        chdir(dirname($root . $path));
        include $root . $path;
        return true;
    }
    return false; // Let PHP's built-in server handle static files
}

// Route everything else through WordPress
chdir($root);
include 'index.php';
