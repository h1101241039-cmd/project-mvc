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