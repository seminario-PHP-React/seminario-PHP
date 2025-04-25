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
    public function getAll(): array {
        $pdo = $this->database->getConnection();
        $stmt = $pdo->query("SELECT * FROM carta");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCardByData(string $atributo, string $nombre): array {
        $pdo = $this->database->getConnection();
        
        // Verifica si la consulta está bien escrita
        $sql = "
            SELECT C.id, C.nombre, C.ataque, C.ataque_nombre, C.imagen, A.nombre AS atributo_nombre
            FROM carta AS C
            LEFT JOIN atributo AS A ON C.atributo_id = A.id
            WHERE A.nombre = :atributo AND C.nombre = :nombre;
        ";
        
        // Preparar y ejecutar la consulta
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':atributo', $atributo, PDO::PARAM_STR);
        $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->execute();
        
        // Mostrar los resultados para depuración
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        if (empty($result)) {
            // Si no hay resultados, devuelve un array vacío
            return [];
        }
    
        return $result;
    }
    
    
    
    
    
    public function getById(int $id): array|bool {
        $sql = 'SELECT * FROM carta WHERE id = :id';
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function create(array $data): string {
        $sql = 'INSERT INTO carta (nombre, ataque, ataque_nombre, imagen, atributo_id) 
                VALUES (:name, :attack, :attack_name, :image, :attribute_id)';
        
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);
    
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':attack', $data['attack'], PDO::PARAM_INT);
        $stmt->bindValue(':attack_name', $data['attack_name'], PDO::PARAM_STR);
        $stmt->bindValue(':imagen', $data['image'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':attribute_id', $data['attribute_id'], PDO::PARAM_INT);
    
        $stmt->execute();
        return $pdo->lastInsertId();
    }
    public function update(int $id, array $data): int {
        $sql = 'UPDATE carta 
                SET nombre = :name, 
                    ataque = :attack, 
                    ataque_nombre = :attack_name, 
                    imagen= :imagen, 
                    atributo_id= :attribute_id
              WHERE id = :id';
        
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);
    
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':attack', $data['attack'], PDO::PARAM_INT);
        $stmt->bindValue(':attack_name', $data['attack_name'], PDO::PARAM_STR);
        $stmt->bindValue(':imagen', $data['image'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':attribute_id', $data['attribute_id'], PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function delete(string $id): int{
        $sql = 'DELETE FROM carta 
        WHERE id = :id';
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
