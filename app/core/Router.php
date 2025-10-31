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
    if (is_string($callback)) {
        [$controllerName, $method] = explode('@', $callback);
        $controllerFile = __DIR__ . "/../controllers/" . $controllerName . ".php";

        if (file_exists($controllerFile)) {
            require_once $controllerFile;

            if (class_exists($controllerName)) {
                $controllerInstance = new $controllerName();

                if (method_exists($controllerInstance, $method)) {
                    return call_user_func_array([$controllerInstance, $method], $params);
                } else {
                    die("❌ Method <b>$method</b> tidak ditemukan di <b>$controllerName</b>");
                }
            } else {
                die("❌ Class <b>$controllerName</b> tidak ditemukan di file $controllerFile");
            }
        } else {
            die("❌ File controller <b>$controllerFile</b> tidak ditemukan");
        }
    }

    return call_user_func_array($callback, $params);
}
}