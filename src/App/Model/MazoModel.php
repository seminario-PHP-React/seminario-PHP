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
        $result = ((int)$stmt->fetchColumn()) === count($cartas);
        $this->database->closeConnection();
        return $result;
    }

    public function cantidadMazosUsuario(int $usuario_id): int {
        $pdo = $this->database->getConnection();
        $query= "SELECT COUNT(*) FROM mazo WHERE usuario_id = :usuario_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = (int)$stmt->fetchColumn();
        $this->database->closeConnection();
        return $result;
    }

    public function crearMazo(int $usuarioId, string $nombre, array $cartas): int {
        $pdo = $this->database->getConnection();
        $pdo->beginTransaction();
    
        try {
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
            $this->database->closeConnection();
            return (int) $mazoId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            $this->database->closeConnection();
            throw $e;
        }
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
        $result = (bool) $stmt->fetchColumn();
        $this->database->closeConnection();
        return $result;
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
        $result = (bool) $stmt->fetchColumn();
        $this->database->closeConnection();
        return $result;
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
        $result = (bool) $stmt->fetchColumn();
        $this->database->closeConnection();
        return $result;
    }

    public function editarNombreMazo(int $mazoId, string $nuevoNombre): void {
        $pdo = $this->database->getConnection();
        $query= "UPDATE mazo SET nombre = :nombre WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':nombre', $nuevoNombre, PDO::PARAM_STR);
        $stmt->bindValue(':id', $mazoId, PDO::PARAM_INT);
        $stmt->execute();
        $this->database->closeConnection();
    }

    public function getUserMazos(int $usuario_id): array {
        $pdo = $this->database->getConnection();
        $query="SELECT id, nombre FROM mazo WHERE usuario_id = :usuario_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $result;
    }

    public function mazoPerteneceAUsuario(int $mazoId, int $usuarioId): bool {
        $pdo = $this->database->getConnection();
        $query = "SELECT COUNT(*) FROM mazo WHERE id = :mazoId AND usuario_id = :usuarioId";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':mazoId', $mazoId, PDO::PARAM_INT);
        $stmt->bindValue(':usuarioId', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        $result = (bool) $stmt->fetchColumn();
        $this->database->closeConnection();
        return $result;
    }
    

    public function eliminarMazoConCartas(int $mazoId): int {
        $pdo = $this->database->getConnection();

        try {
            // Verifica si el mazo participó en una partida
            $query= "SELECT COUNT(*) FROM partida WHERE mazo_id = :mazoId";
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':mazoId', $mazoId, PDO::PARAM_INT);
            $stmt->execute();

            if ((int)$stmt->fetchColumn() > 0) {
                $this->database->closeConnection();
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
            $result = $stmtMazo->rowCount();
            $this->database->closeConnection();
            return $result;
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->database->closeConnection();
            throw $e;
        }
    }   
    
    public function cartasEnMano(int $mazoId): array {
        $pdo = $this->database->getConnection();
        $query = 'SELECT MC.carta_id FROM mazo M
        LEFT JOIN mazo_carta MC ON MC.mazo_id = :mazoId
        WHERE MC.estado = \'en_mano\' AND m.usuario_id = 1';

        $stmt = $pdo->prepare($query);
        $stmt->execute(['mazoId' => $mazoId]);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $result;
    }

    public function actualizarEstadoMazo(string $estado, int $mazoId): void {
        $pdo = $this->database->getConnection();
        $query = 'UPDATE mazo_carta SET estado = :estado WHERE mazo_id = :mazoId';
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'mazoId' => $mazoId,
            'estado' => $estado
        ]);
        $this->database->closeConnection();
    }
    
    
}
