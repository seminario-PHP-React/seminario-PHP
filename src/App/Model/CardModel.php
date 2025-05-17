<?php

declare(strict_types=1);
namespace App\Model;
use App\Database;
use PDO;

class CardModel
{
    public function __construct(private Database $database)
    {
        
    }
   

    public function getCardByData(?string $atributo, ?string $nombre): array {
        $pdo = $this->database->getConnection();
        
        $query = "
            SELECT C.id, C.nombre, C.ataque, C.ataque_nombre, C.imagen, A.nombre AS atributo
            FROM carta AS C
            LEFT JOIN atributo AS A ON C.atributo_id = A.id
            WHERE 1 = 1 
        ";
        
        if ($atributo !== null) {
            $query .= " AND LOWER(A.nombre) LIKE :atributo";
        }
        
        if ($nombre !== null) {
            $query .= " AND LOWER(C.nombre) LIKE :nombre";
        }
        
    
        $stmt = $pdo->prepare($query);
    
        if ($atributo) {
            $stmt->bindValue(':atributo', strtolower($atributo), PDO::PARAM_STR); 
        }
        
    
        if ($nombre) {
            $stmt->bindValue(':nombre', strtolower($nombre), PDO::PARAM_STR); 
        }
    
       
        $stmt->execute();
        $this->database->closeConnection();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCards(): array {
        $pdo = $this->database->getConnection();
        $query = "SELECT * FROM carta";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $result;
    }

    public function getCardById(int $id): ?array {
        $pdo = $this->database->getConnection();
        $query = "SELECT * FROM carta WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $result ?: null;
    }

    public function getCardsByIds(array $ids): array {
        if (empty($ids)) {
            return [];
        }
        $pdo = $this->database->getConnection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = "SELECT * FROM carta WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($query);
        $stmt->execute($ids);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $result;
    }
}
