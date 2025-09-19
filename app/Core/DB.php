<?php

namespace App\Core;

use PDO;
use PDOException;

class DB
{
    private static ?PDO $instance = null;
    
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dbConfig = Config::get('database');
            
            try {
                $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
                self::$instance = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
            } catch (PDOException $e) {
                error_log("Database connection error: " . $e->getMessage());
                throw new \Exception("Database connection failed. Please check your configuration.");
            }
        }
        
        return self::$instance;
    }
    
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }
    
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }
    
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }
    
    public static function rollback(): bool
    {
        return self::getInstance()->rollback();
    }
}