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
        $stmt->bindValue(':mazo_id', $data['mazo_id'], PDO::PARAM_INT);
        $stmt->bindValue(':state', $data['state']);
    
        $stmt->execute();
        return $pdo->lastInsertId();
    }
    
}