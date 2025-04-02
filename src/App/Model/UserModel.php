<?php
declare(strict_types=1);
namespace App\Model;
use App\Database;
use PDO;

class UserModel{

    public function __construct(private Database $database)
    {
        
    }
    public function create(array $data):void{
        $sql = "INSERT INTO usuario (nombre, usuario, password_hash, token, vencimiento_token ) VALUES (:name, :user_name, :password_hash, :api_key, :api_key_expiration)";
        $pdo = $this->database ->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':user_name', $data['user_name']);
        $stmt->bindValue(':password_hash', $data['password_hash']);
        $stmt->bindValue(':api_key', $data['api_key']);
        $stmt->bindValue(':api_key_expiration', $data['api_key_expiration']);

        $stmt->execute();
    }
    public function userExists(string $userName): bool
    {
        $sql = "SELECT COUNT(*) FROM usuario WHERE usuario = :user_name";
        $pdo = $this->database ->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_name', $userName);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return $result > 0;  
    }

    public function find(string $column, $value):array|bool
    {
        $sql = "SELECT * FROM usuario WHERE $column = :value";
        $pdo = $this->database ->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':value', $value);
        $stmt->execute();
        return $stmt->fetch();
    }
    public function update(int $id, string $column, $value): void{
        $sql = "UPDATE usuario SET $column = :value WHERE id = :id";

        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(':value', $value);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();
    }
}