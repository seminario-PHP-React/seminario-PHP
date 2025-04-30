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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
}
