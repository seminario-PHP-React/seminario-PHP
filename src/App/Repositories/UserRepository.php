<?php
declare(strict_types=1);
namespace App\Repositories;
use App\Database;

class UserRepository{

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
        return $result > 0;  // Si hay 1 o más resultados, el usuario ya existe
    }

}