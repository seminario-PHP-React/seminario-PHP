<?php
declare(strict_types=1);
namespace App\Model;
use App\Database;
use PDO;
use Exception;

class PartidaModel {
    public function __construct(private Database $database) {}

    public function create(array $data): string {
        $pdo = $this->database->getConnection();
        $pdo->beginTransaction();

        try {
            if (!$this->mazoExiste((int)$data['mazo_id'])) {
                throw new Exception('El mazo no existe');
            }

            if (!$this->usuarioExiste((int)$data['user_id'])) {
                throw new Exception('El usuario no existe');
            }

            $query = 'INSERT INTO partida (usuario_id, fecha, mazo_id, estado) 
                    VALUES (:user_id, :date, :mazo_id, :state)';
            
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':date', $data['date']);
            $stmt->bindValue(':mazo_id', (int)$data['mazo_id'], PDO::PARAM_INT);
            $stmt->bindValue(':state', $data['state']);
            $stmt->execute();
        
            $lastInsertId = $pdo->lastInsertId();
        
            $updateServerCards = 'UPDATE mazo_carta SET estado = :estado WHERE mazo_id = :mazo_id';
            $updateStmtS = $pdo->prepare($updateServerCards);
            $updateStmtS->bindValue(':estado', 'en_mano');
            $updateStmtS->bindValue(':mazo_id', 1, PDO::PARAM_INT);
            $updateStmtS->execute();

            $updateUserCards = 'UPDATE mazo_carta SET estado = :estado WHERE mazo_id = :mazo_id';
            $updateStmtU = $pdo->prepare($updateUserCards);
            $updateStmtU->bindValue(':estado', 'en_mano');
            $updateStmtU->bindValue(':mazo_id', (int)$data['mazo_id'], PDO::PARAM_INT);
            $updateStmtU->execute();

            $pdo->commit();
            $this->database->closeConnection();
            return $lastInsertId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            $this->database->closeConnection();
            throw $e;
        }
    }

    private function mazoExiste(int $mazoId): bool {
        $pdo = $this->database->getConnection();
        $query = "SELECT COUNT(*) FROM mazo WHERE id = :mazo_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':mazo_id', $mazoId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchColumn() > 0;
        $this->database->closeConnection();
        return $result;
    }

    private function usuarioExiste(int $userId): bool {
        $pdo = $this->database->getConnection();
        $query = "SELECT COUNT(*) FROM usuario WHERE id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchColumn() > 0;
        $this->database->closeConnection();
        return $result;
    }
    
    public function findMazoByIdAndUser(int $mazoId, int $userId): array|false {
        $pdo = $this->database->getConnection();
        $query = "SELECT * FROM mazo WHERE id = :mazoId AND usuario_id = :userId";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':mazoId', $mazoId, PDO::PARAM_INT);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
    
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $result;
    }
    
    public function getCartas(int $mazoId): array {
        $pdo = $this->database->getConnection();
        $query = "
            SELECT c.id, c.nombre
            FROM mazo_carta mc
            INNER JOIN carta c ON mc.carta_id = c.id
            WHERE mc.mazo_id = :mazoId
            ORDER BY c.nombre;
        ";
    
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':mazoId', $mazoId, PDO::PARAM_INT);  
        $stmt->execute();
    
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $result;
    }
    
    public function getCartasMano(int $usuario, int $partida): array {
        if (!$this->partidaPerteneceAUsuario($partida, $usuario)) {
            throw new Exception('La partida no pertenece al usuario');
        }

        $pdo = $this->database->getConnection();
        $query = "
            SELECT mc.carta_id, c.nombre, c.ataque_nombre, c.ataque, a.nombre AS atributo_nombre 
            FROM partida p 
            INNER JOIN mazo m ON m.id = p.mazo_id
            INNER JOIN mazo_carta mc ON mc.mazo_id = p.mazo_id
            INNER JOIN carta c ON mc.carta_id = c.id
            INNER JOIN atributo a ON c.atributo_id = a.id
            WHERE p.id = :partida AND p.usuario_id = :usuario AND mc.estado = 'en_mano';
        ";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':partida', $partida, PDO::PARAM_INT); 
        $stmt->bindParam(':usuario', $usuario, PDO::PARAM_INT);   
        $stmt->execute();
    
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $result;
    }

    private function partidaPerteneceAUsuario(int $partidaId, int $usuarioId): bool {
        $pdo = $this->database->getConnection();
        $query = "SELECT COUNT(*) FROM partida WHERE id = :partida_id AND usuario_id = :usuario_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':partida_id', $partidaId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchColumn() > 0;
        $this->database->closeConnection();
        return $result;
    }

    public function getMazoPorPartida(int $partida, int $usuario): array|bool {
        $pdo = $this->database->getConnection();
        $query = "
            SELECT p.id, p.usuario_id, p.estado
            FROM partida p
            INNER JOIN mazo m ON p.mazo_id = m.id
            WHERE p.id = :partida AND p.usuario_id = :usuario;
        ";
    
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':partida', $partida, PDO::PARAM_INT);
        $stmt->bindParam(':usuario', $usuario, PDO::PARAM_INT);
        $stmt->execute();
    
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $result; 
    }

    public function actualizarEstadoPartida(string $estado, int $partidaId): void {
        $pdo = $this->database->getConnection();
        $updateSql = 'UPDATE partida P SET estado = :estado WHERE P.id = :partidaId';
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->bindValue(':estado', $estado, PDO::PARAM_STR);
        $updateStmt->bindValue(':partidaId', $partidaId, PDO::PARAM_INT);
        $updateStmt->execute();
        $this->database->closeConnection();
    }
    
    public function encontrarMazoPorPartida(int $partidaId): int {
        $pdo = $this->database->getConnection();
        $query = 'SELECT mazo_id FROM partida WHERE id = :partidaId';
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':partidaId', $partidaId, PDO::PARAM_INT);
        $stmt->execute();
        $mazo = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->database->closeConnection();

        if (!$mazo) {
            throw new Exception('La partida no existe');
        }

        return (int)$mazo['mazo_id'];
    }
  
    public function mazoEnUso(int $mazoId, int $usuarioId): bool {
        $pdo = $this->database->getConnection();
        $query = "SELECT COUNT(*) 
                  FROM partida 
                  WHERE mazo_id = :mazo_id 
                    AND usuario_id = :usuario_id 
                    AND estado = 'en_curso'";

        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':mazo_id', $mazoId, PDO::PARAM_INT);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchColumn() > 0;
        $this->database->closeConnection();
        return $result;
    }

    public function mazoServidorEnUso(int $mazoId): bool {
        $pdo = $this->database->getConnection();
        $query = "SELECT COUNT(*) FROM mazo_carta WHERE mazo_id = :mazo_id AND estado = 'en_mano'";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':mazo_id', $mazoId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchColumn() > 0;
        $this->database->closeConnection();
        return $result;
    }
    
    public function resultadoUsuario(string $rto, int $partidaId): void {
        $pdo = $this->database->getConnection();
        $query = "UPDATE partida SET el_usuario = :rto WHERE id = :partidaId";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':rto', $rto, PDO::PARAM_STR); 
        $stmt->bindValue(':partidaId', $partidaId, PDO::PARAM_INT);
        $stmt->execute(); 
        $this->database->closeConnection();
    }
}