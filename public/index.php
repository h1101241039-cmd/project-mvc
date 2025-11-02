<?php
// Autoloader sederhana
spl_autoload_register(function($class) {
    $class = str_replace('App\\', '', $class);
    $class = str_replace('\\', '/', $class);
    $file = __DIR__ . '/../app/' . $class . '.php';
    
    if(file_exists($file)) {
        require_once $file;
    }
});

// Load core files
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Router.php';

// Inisialisasi Router
$router = new Router();

// Define Routes
$router->get('/', 'HomeController@index');
$router->get('/users', 'UserController@index');
$router->get('/users/{id}', 'UserController@show');
$router->post('/users', 'UserController@store');

// Resolve request
$router->resolve();