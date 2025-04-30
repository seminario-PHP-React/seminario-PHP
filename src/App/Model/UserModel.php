<?php
declare(strict_types=1);
namespace App\Model;
use App\Database;
use PDO;

class UserModel{

    public function __construct(private Database $database)
    {
        
    }
    public function create(array $data): int
    {
        $query = "INSERT INTO usuario (nombre, usuario, password)
                VALUES (:name, :user_name, :password_hash)";
        
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':user_name', $data['user_name']);
        $stmt->bindValue(':password_hash', $data['password_hash']);
        
    
        $stmt->execute();
    
        return (int) $pdo->lastInsertId(); 
    }
    
    public function userExists(string $userName): bool
    {
        $query = "SELECT COUNT(*) FROM usuario WHERE usuario = :user_name";
        $pdo = $this->database ->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_name', $userName);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return $result > 0;  
    }

    public function find(string $column, $value):array|bool
    {
        $query = "SELECT * FROM usuario WHERE $column = :value";
        $pdo = $this->database ->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':value', $value);
        $stmt->execute();
        return $stmt->fetch();
    }
    public function update(int $id, string $column, $value): void{
        $query = "UPDATE usuario SET $column = :value WHERE id = :id";

        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);

        $stmt->bindValue(':value', $value);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();
    }
    public function updateApiKey(int $userId, string $apiKey, string $expiration): void
    {
        $query = "UPDATE usuario 
                SET token = :token, vencimiento_token = :token_expiration 
                WHERE id = :id";

        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);

        $stmt->bindValue(':token', $apiKey);
        $stmt->bindValue(':token_expiration', $expiration);
        $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);

        $stmt->execute();
    }
    public function findById(int $userId): ?array
    {
        $query = "SELECT * FROM usuario WHERE id = :user_id";
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null; // Devuelve el usuario o null si no se encuentra
    }
    public function getAPIKey(int $id){
        $query= 'SELECT token FROM usuario WHERE id = :id';
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $token= $stmt->fetch(PDO::FETCH_ASSOC);
        return $token['token'];
    }

}