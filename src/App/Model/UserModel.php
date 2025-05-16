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
                VALUES (:nombre, :usuario, :password)";
        
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        
        $stmt->bindValue(':nombre', $data['nombre']);
        $stmt->bindValue(':usuario', $data['usuario']);
        $stmt->bindValue(':password', $data['password_hash']);
        
        $stmt->execute();
    
        $result = (int) $pdo->lastInsertId();
        $this->database->closeConnection();
        return $result; 
    }
    
    public function userExists(string $usuario): bool
    {
        $query = "SELECT COUNT(*) FROM usuario WHERE usuario = :usuario";
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        $this->database->closeConnection();
        return $result > 0;  
    }

    public function find(string $column, $value): array|bool
    {
        $query = "SELECT * FROM usuario WHERE $column = :value";
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':value', $value);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $result ?: false;
    }
    public function update(int $id, string $column, $value): void
    {
        $query = "UPDATE usuario SET $column = :value WHERE id = :id";
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':value', $value);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $this->database->closeConnection();
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
        $this->database->closeConnection();
    }
    public function findById(int $userId): ?array
    {
        $query = "SELECT * FROM usuario WHERE id = :user_id";
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':user_id', $userId);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $result ?: null; // Devuelve el usuario o null si no se encuentra
    }
    public function getAPIKey(int $id): ?string
    {
        $query = 'SELECT token FROM usuario WHERE id = :id';
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $token = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $token ? $token['token'] : null;
    }

}