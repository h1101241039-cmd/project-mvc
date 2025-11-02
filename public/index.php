<?php
// Autoloader sederhana
spl_autoload_register(function($class) {
    $classPath = str_replace(['App\\', '\\'], ['', '/'], $class);
    $baseDir = dirname(__DIR__) . '/App/'; // tetap ke folder app dari public/
    $file = $baseDir . $classPath . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    } else {
        error_log("File not found class: $class ($file)");
    }
});

// Load core files
require_once __DIR__ . '/../App/Core/Database.php';
require_once __DIR__ . '/../App/Core/Router.php';

// Inisialisasi Router
$router = new Router();

// Define Routes
$router->get('/', 'HomeController@index');
$router->get('/users', 'UserController@index');
$router->get('/users/{id}', 'UserController@show');
$router->post('/users', 'UserController@store');

// Resolve request
$router->resolve();
