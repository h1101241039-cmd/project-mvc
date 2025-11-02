// ============================================
// STRUKTUR FOLDER PROYEK
// ============================================
/*
project-mvc/
├── app/
│   ├── controllers/
│   │   ├── HomeController.php
│   │   └── UserController.php
│   ├── models/
│   │   └── User.php
│   ├── views/
│   │   ├── home/
│   │   │   └── index.php
│   │   ├── users/
│   │   │   ├── index.php
│   │   │   └── show.php
│   │   └── layouts/
│   │       └── main.php
│   └── core/
│       ├── Database.php
│       ├── Controller.php
│       ├── Model.php
│       └── Router.php
├── public/
│   ├── index.php
│   └── .htaccess
└── config/
    └── database.php
*/

// ============================================
// FILE: config/database.php
// ============================================
<?php
return [
    'host' => 'localhost',
    'dbname' => 'mvc_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

// ============================================
// FILE: app/core/Database.php
// ============================================
<?php
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        $config = require_once __DIR__ . '/../../config/database.php';
        
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $this->conn = new PDO($dsn, $config['username'], $config['password']);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    private function __clone() {}
    public function __wakeup() {}
}

// ============================================
// FILE: app/core/Router.php
// ============================================
<?php
class Router {
    private $routes = [];
    
    public function get($path, $callback) {
        $this->routes['GET'][$path] = $callback;
    }
    
    public function post($path, $callback) {
        $this->routes['POST'][$path] = $callback;
    }
    
    public function resolve() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = str_replace('/public', '', $path);
        
        if($path === '') $path = '/';
        
        // Cek route dengan parameter dinamis
        foreach($this->routes[$method] ?? [] as $route => $callback) {
            $pattern = preg_replace('/\{([a-zA-Z]+)\}/', '([a-zA-Z0-9]+)', $route);
            $pattern = "#^" . $pattern . "$#";
            
            if(preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                return $this->executeCallback($callback, $matches);
            }
        }
        
        // Route tidak ditemukan
        http_response_code(404);
        echo "404 - Page Not Found";
    }
    
    private function executeCallback($callback, $params = []) {
        if(is_string($callback)) {
            $parts = explode('@', $callback);
            $controller = "App\\Controllers\\" . $parts[0];
            $method = $parts[1];
            
            if(class_exists($controller)) {
                $controllerInstance = new $controller();
                return call_user_func_array([$controllerInstance, $method], $params);
            }
        }
        
        return call_user_func_array($callback, $params);
    }
}

// ============================================
// FILE: app/core/Controller.php
// ============================================
<?php
namespace App\Core;

class Controller {
    protected function view($view, $data = []) {
        extract($data);
        $viewPath = __DIR__ . '/../views/' . str_replace('.', '/', $view) . '.php';
        
        if(file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            die("View not found: {$view}");
        }
    }
    
    protected function redirect($path) {
        header("Location: {$path}");
        exit;
    }
    
    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// ============================================
// FILE: app/core/Model.php
// ============================================
<?php
namespace App\Core;

class Model {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    public function all() {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }
    
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    public function update($id, $data) {
        $set = [];
        foreach($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }
        $set = implode(', ', $set);
        
        $sql = "UPDATE {$this->table} SET {$set} WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

// ============================================
// FILE: app/models/User.php
// ============================================
<?php
namespace App\Models;
use App\Core\Model;

class User extends Model {
    protected $table = 'users';
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }
}

// ============================================
// FILE: app/controllers/HomeController.php
// ============================================
<?php
namespace App\Controllers;
use App\Core\Controller;

class HomeController extends Controller {
    public function index() {
        $data = [
            'title' => 'Home Page',
            'message' => 'Selamat datang di MVC Framework'
        ];
        
        $this->view('home.index', $data);
    }
}

// ============================================
// FILE: app/controllers/UserController.php
// ============================================
<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Models\User;

class UserController extends Controller {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function index() {
        $users = $this->userModel->all();
        $this->view('users.index', ['users' => $users]);
    }
    
    public function show($id) {
        $user = $this->userModel->find($id);
        
        if(!$user) {
            http_response_code(404);
            echo "User not found";
            return;
        }
        
        $this->view('users.show', ['user' => $user]);
    }
    
    public function store() {
        $data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? ''
        ];
        
        if($this->userModel->create($data)) {
            $this->redirect('/users');
        } else {
            echo "Failed to create user";
        }
    }
}

// ============================================
// FILE: app/views/layouts/main.php
// ============================================
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'MVC App' ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        nav { background: #333; color: white; padding: 1rem; margin-bottom: 2rem; }
        nav a { color: white; text-decoration: none; margin-right: 1rem; }
    </style>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/users">Users</a>
    </nav>
    <div class="container">
        <?= $content ?? '' ?>
    </div>
</body>
</html>

// ============================================
// FILE: app/views/home/index.php
// ============================================
<?php ob_start(); ?>
<h1><?= htmlspecialchars($title) ?></h1>
<p><?= htmlspecialchars($message) ?></p>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/main.php'; ?>

// ============================================
// FILE: app/views/users/index.php
// ============================================
<?php ob_start(); ?>
<h1>Daftar Users</h1>
<table border="1" cellpadding="10">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($users as $user): ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><?= htmlspecialchars($user['name']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><a href="/users/<?= $user['id'] ?>">View</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/main.php'; ?>

// ============================================
// FILE: app/views/users/show.php
// ============================================
<?php ob_start(); ?>
<h1>Detail User</h1>
<p><strong>ID:</strong> <?= $user['id'] ?></p>
<p><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
<a href="/users">Kembali</a>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/main.php'; ?>

// ============================================
// FILE: public/.htaccess
// ============================================
/*
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
*/

// ============================================
// FILE: public/index.php
// ============================================
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

// ============================================
// SQL UNTUK MEMBUAT DATABASE DAN TABLE
// ============================================
/*
CREATE DATABASE mvc_db;

USE mvc_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (name, email) VALUES 
('John Doe', 'john@example.com'),
('Jane Smith', 'jane@example.com');
*/