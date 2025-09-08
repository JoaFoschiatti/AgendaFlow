<?php

namespace App\Core;

use PDO;

abstract class Model
{
    protected string $table;
    protected array $fillable = [];
    protected string $primaryKey = 'id';
    protected PDO $db;
    
    public function __construct()
    {
        $this->db = DB::getInstance();
    }
    
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = DB::query($sql, ['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function findBy(string $column, $value): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :value";
        $stmt = DB::query($sql, ['value' => $value]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function all(array $conditions = [], array $orderBy = [], ?int $limit = null): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        if (!empty($orderBy)) {
            $orders = [];
            foreach ($orderBy as $column => $direction) {
                $orders[] = "{$column} {$direction}";
            }
            $sql .= " ORDER BY " . implode(', ', $orders);
        }
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = DB::query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function create(array $data): ?int
    {
        $data = $this->filterFillable($data);
        
        if (empty($data)) {
            return null;
        }
        
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        try {
            DB::query($sql, $data);
            return (int) DB::lastInsertId();
        } catch (\PDOException $e) {
            error_log("Insert error: " . $e->getMessage());
            return null;
        }
    }
    
    public function update(int $id, array $data): bool
    {
        $data = $this->filterFillable($data);
        
        if (empty($data)) {
            return false;
        }
        
        $sets = [];
        foreach ($data as $column => $value) {
            $sets[] = "{$column} = :{$column}";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE {$this->primaryKey} = :id";
        $data['id'] = $id;
        
        try {
            $stmt = DB::query($sql, $data);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        
        try {
            $stmt = DB::query($sql, ['id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Delete error: " . $e->getMessage());
            return false;
        }
    }
    
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $stmt = DB::query($sql, $params);
        $result = $stmt->fetch();
        return (int) $result['total'];
    }
    
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    public function paginate(int $page = 1, int $perPage = 15, array $conditions = [], array $orderBy = []): array
    {
        $offset = ($page - 1) * $perPage;
        $total = $this->count($conditions);
        $totalPages = ceil($total / $perPage);
        
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        if (!empty($orderBy)) {
            $orders = [];
            foreach ($orderBy as $column => $direction) {
                $orders[] = "{$column} {$direction}";
            }
            $sql .= " ORDER BY " . implode(', ', $orders);
        }
        
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $stmt = DB::query($sql, $params);
        
        return [
            'data' => $stmt->fetchAll(),
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages
        ];
    }
}