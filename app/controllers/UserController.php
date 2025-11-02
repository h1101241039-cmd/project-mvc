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
