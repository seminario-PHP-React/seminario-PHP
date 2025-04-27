<?php

declare(strict_types=1);
namespace App\Model;
use App\Database;
use PDO;

class EstadisticaModel
{
    public function __construct(private Database $database)
    {
        
    }

    public function getEstadistica(){
        $query = 'SELECT u.nombre, 
                         p.usuario_id,
                         COUNT(*) AS total_jugadas,
                         SUM(CASE WHEN p.el_usuario = \'gano\' THEN 1 ELSE 0 END) AS victorias,
                         SUM(CASE WHEN p.el_usuario = \'empato\' THEN 1 ELSE 0 END) AS empates,
                         SUM(CASE WHEN p.el_usuario = \'perdio\' THEN 1 ELSE 0 END) AS derrotas
                  FROM partida p
                  LEFT JOIN jugada j ON p.id = j.partida_id
                  LEFT JOIN usuario u ON p.usuario_id = u.id
                  GROUP BY p.usuario_id, u.nombre;';  // Agrupamos por usuario_id y nombre de usuario
    
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->execute();
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    


}