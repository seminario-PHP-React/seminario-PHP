<?php
declare(strict_types=1);
namespace App\Model;
use App\Database;
use PDO;


class PartidaModel{

    public function __construct(private Database $database)
    {
    
    }
    public function create(array $data): string {
        $sql = 'INSERT INTO partida (usuario_id, fecha, mazo_id, estado) 
                VALUES (:user_id, :date, :mazo_id, :state)';
        
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($sql);
    
        $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':date', $data['date']);
        $stmt->bindValue(':mazo_id', (int)$data['mazo_id'], PDO::PARAM_INT);
        $stmt->bindValue(':state', $data['state']);
        $stmt->execute();
    
        $lastInsertId = $pdo->lastInsertId();
    
        $updateSql = 'UPDATE mazo_carta SET estado = :estado WHERE mazo_id = :mazo_id';
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->bindValue(':estado', 'en_mazo');
        $updateStmt->bindValue(':mazo_id', (int)$data['mazo_id'], PDO::PARAM_INT);
        $updateStmt->execute();
    
        return $lastInsertId;
    }
    
    public function find($id) {
        $query = "SELECT * FROM mazo WHERE usuario_id = :id";
        $pdo= $this->database->getConnection();
        $stmt = $pdo->prepare($query);

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC); 
    }
    
    public function getCartas($mazoId) {
        $query = "
            SELECT c.id, c.nombre
            FROM mazo_carta mc
            INNER JOIN carta c ON mc.carta_id = c.id
            WHERE mc.mazo_id = :mazoId
            ORDER BY c.nombre;
        ";
    
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':mazoId', $mazoId, PDO::PARAM_INT);  
        $stmt->execute();
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCartasMano($usuario, $partida) {
        $query= "
            SELECT mc.carta_id, c.nombre, c.ataque_nombre, a.nombre AS atributo_nombre FROM partida p 
            LEFT JOIN mazo m ON m.id = p.mazo_id
            LEFT JOIN mazo_carta mc ON mc.mazo_id = p.mazo_id
            LEFT JOIN carta c ON mc.carta_id = c.id
            LEFT JOIN atributo a ON c.atributo_id = a.id
            WHERE p.id = :partida AND p.usuario_id = :usuario AND mc.estado = 'en_mazo';
        ";
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':partida', $partida, PDO::PARAM_INT); 
        $stmt->bindParam(':usuario', $usuario, PDO::PARAM_INT);   
        $stmt->execute();
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getMazoPorPartida($partida, $usuario): array| bool {
        
        $query = "
            SELECT p.id, p.usuario_id, p.estado
            FROM partida p
            LEFT JOIN mazo m ON p.mazo_id = m.id
            WHERE p.id = :partida AND p.usuario_id = :usuario;

        ";
    
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':partida', $partida, PDO::PARAM_INT);
        $stmt->bindParam(':usuario', $usuario, PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt->fetch(PDO::FETCH_ASSOC); 
    }
    
  
    
    
}