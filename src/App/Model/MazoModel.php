<?php
namespace App\Model;

use App\Database;
use PDO;

class MazoModel {
   

    public function __construct(private Database $database) {
      
    }

    public function existenCartas(array $cartas): bool {
        if (empty($cartas)) {
            return false;
        }
        $pdo = $this->database->getConnection();
        $placeholders = implode(',', array_fill(0, count($cartas), '?'));
        $query = "SELECT COUNT(*) FROM carta WHERE id IN ($placeholders)";

        $stmt = $pdo->prepare($query);
        $stmt->execute($cartas);
        return ((int)$stmt->fetchColumn()) === count($cartas);
    }

    public function cantidadMazosUsuario(int $usuario_id): int {
        $pdo = $this->database->getConnection();
        $query= "SELECT COUNT(*) FROM mazo WHERE usuario_id = :usuario_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function crearMazo(int $usuarioId, string $nombre, array $cartas): int {
        $pdo = $this->database->getConnection();
        $pdo->beginTransaction();
    
        // Inserta el mazo
        $query = "INSERT INTO mazo (usuario_id, nombre) VALUES (:usuario_id, :nombre)";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->execute();
    
        $mazoId = $pdo->lastInsertId();
    
       
        $queryCartas = "INSERT INTO mazo_carta (mazo_id, carta_id, estado) VALUES (:mazo_id, :carta_id, 'en_mazo')";
        $stmtCartas = $pdo->prepare($queryCartas);
        $stmtCartas->bindParam(':mazo_id', $mazoId, PDO::PARAM_INT);
        $stmtCartas->bindParam(':carta_id', $cartaId, PDO::PARAM_INT);
    
        foreach ($cartas as $cartaId) {
            $stmtCartas->execute();
        }
    
        $pdo->commit();
        return (int) $mazoId;
    }
    
    
    public function nombreMazoExiste(int $usuarioId, string $nombre, int $mazoIdActual): bool {
        $pdo = $this->database->getConnection();
        $query= "
            SELECT COUNT(*) FROM mazo 
            WHERE usuario_id = :usuario_id AND nombre = :nombre AND id != :mazo_id
        ";

        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindValue(':mazo_id', $mazoIdActual, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    public function nombreNuevoMazoExiste(int $usuarioId, string $nombre): bool {
        $pdo = $this->database->getConnection();
        $query= "
            SELECT COUNT(*) FROM mazo 
            WHERE usuario_id = :usuario_id AND nombre = :nombre;
        ";

        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
       
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    public function mazoExiste(int $usuarioId, int $mazoIdActual): bool {
        $pdo = $this->database->getConnection();
        $query= "
            SELECT COUNT(*) FROM mazo 
            WHERE usuario_id = :usuario_id AND id = :mazo_id
        ";

        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':mazo_id', $mazoIdActual, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    public function editarNombreMazo(int $mazoId, string $nuevoNombre): void {
        $pdo = $this->database->getConnection();
        $query= "UPDATE mazo SET nombre = :nombre WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':nombre', $nuevoNombre, PDO::PARAM_STR);
        $stmt->bindValue(':id', $mazoId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function getUserMazos(int $usuario_id): array {
        $pdo = $this->database->getConnection();
        $query="SELECT id, nombre FROM mazo WHERE usuario_id = :usuario_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function mazoPerteneceAUsuario(int $mazoId, int $usuarioId): bool {
        $pdo = $this->database->getConnection();
        $query="SELECT COUNT(*) FROM mazo WHERE id = :mazoId AND usuario_id = :usuarioId";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':mazoId', $mazoId, PDO::PARAM_INT);
        $stmt->bindValue(':usuarioId', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    public function eliminarMazoConCartas(int $mazoId): int {
        $pdo = $this->database->getConnection();

         // Verifica si el mazo participó en una partida
        $query= "SELECT COUNT(*) FROM partida WHERE mazo_id = :mazoId";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':mazoId', $mazoId, PDO::PARAM_INT);
        $stmt->execute();

        if ((int)$stmt->fetchColumn() > 0) {
            throw new \Exception("El mazo ha participado de una partida y no puede ser eliminado");
        }

        $pdo->beginTransaction();

        // Elimina cartas
        $queryDeleteCarta="DELETE FROM mazo_carta WHERE mazo_id = :mazoId";
        $stmtCartas = $pdo->prepare($queryDeleteCarta);
        $stmtCartas->bindValue(':mazoId', $mazoId, PDO::PARAM_INT);
        $stmtCartas->execute();

        // Elimina mazo
        $queryDeleteMazo="DELETE FROM mazo WHERE id = :mazoId";
        $stmtMazo = $pdo->prepare($queryDeleteMazo);
        $stmtMazo->bindValue(':mazoId', $mazoId, PDO::PARAM_INT);
        $stmtMazo->execute();

        $pdo->commit();
        return $stmtMazo->rowCount();
    }   
    
    public function cartasEnMano(int $mazoId): array {
        $query = 'SELECT MC.carta_id FROM mazo M
        LEFT JOIN mazo_carta MC ON MC.mazo_id = :mazoId
        WHERE MC.estado = \'en_mano\' AND m.usuario_id = 1';

        $stmt = $this->db->prepare($query);

        $stmt->execute(['mazoId' => $mazoId]);
       
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function actualizarEstadoMazo(string $estado, int $mazoId){
        $query = 'UPDATE mazo_carta SET estado = :estado WHERE mazo_id = :mazoId';
        $stmt = $this->db->prepare($query);

        $stmt->execute(['mazoId' => $mazoId,
        'estado'=> $estado
        ]);
       
        $stmt->execute();


    }
   
    
}
