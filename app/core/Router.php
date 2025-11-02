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
