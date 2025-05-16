<?php
declare(strict_types=1);

namespace App\Model;

use App\Database;
use PDO;

class PartidaModel
{
    public function __construct(private Database $database)
    {
    }

    public function create(array $data): string
    {
        $query = 'INSERT INTO partida (usuario_id, fecha, mazo_id, estado) 
                  VALUES (:user_id, :date, :mazo_id, :state)';

        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);

        $stmt->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':date', $data['date']);
        $stmt->bindValue(':mazo_id', (int)$data['mazo_id'], PDO::PARAM_INT);
        $stmt->bindValue(':state', $data['state']);
        $stmt->execute();

        $lastInsertId = $pdo->lastInsertId();

        // Poner cartas del servidor en 'en_mano'
        $updateServerCards = 'UPDATE mazo_carta SET estado = :estado WHERE mazo_id = :mazo_id';
        $updateStmtS = $pdo->prepare($updateServerCards);
        $updateStmtS->bindValue(':estado', 'en_mano');
        $updateStmtS->bindValue(':mazo_id', 1, PDO::PARAM_INT); // 1 es mazo servidor
        $updateStmtS->execute();

        // Poner cartas del usuario en 'en_mano'
        $updateUserCards = 'UPDATE mazo_carta SET estado = :estado WHERE mazo_id = :mazo_id';
        $updateStmtU = $pdo->prepare($updateUserCards);
        $updateStmtU->bindValue(':estado', 'en_mano');
        $updateStmtU->bindValue(':mazo_id', (int)$data['mazo_id'], PDO::PARAM_INT);
        $updateStmtU->execute();

        return $lastInsertId;
    }

    public function findMazoByIdAndUser(int $mazoId, int $userId): array|false
    {
        $query = "SELECT * FROM mazo WHERE id = :mazoId AND usuario_id = :userId";
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':mazoId', $mazoId, PDO::PARAM_INT);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCartas(int $mazoId): array
    {
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

    public function getCartasMano(int $usuario, int $partida): array
    {
        $query = "
            SELECT mc.carta_id, c.nombre, c.ataque_nombre, a.nombre AS atributo_nombre 
            FROM partida p 
            LEFT JOIN mazo m ON m.id = p.mazo_id
            LEFT JOIN mazo_carta mc ON mc.mazo_id = p.mazo_id
            LEFT JOIN carta c ON mc.carta_id = c.id
            LEFT JOIN atributo a ON c.atributo_id = a.id
            WHERE p.id = :partida AND p.usuario_id = :usuario AND mc.estado = 'en_mano';
        ";

        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':partida', $partida, PDO::PARAM_INT);
        $stmt->bindParam(':usuario', $usuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMazoPorPartida(int $partida, int $usuario): array|false
    {
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

    public function actualizarEstadoPartida(string $estado, int $partidaId): void
    {
        $updateSql = 'UPDATE partida P SET estado = :estado WHERE P.id = :partidaId';
        $pdo = $this->database->getConnection();
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->bindValue(':estado', $estado, PDO::PARAM_STR);
        $updateStmt->bindValue(':partidaId', $partidaId, PDO::PARAM_INT);
        $updateStmt->execute();
    }

    public function encontrarMazoPorPartida(int $partidaId): int
    {
        $query = 'SELECT mazo_id FROM partida WHERE id = :partidaId';
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':partidaId', $partidaId, PDO::PARAM_INT);
        $stmt->execute();
        $mazo = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $mazo['mazo_id'];
    }

    public function mazoEnUso(int $mazoId): bool
    {
        $query = "SELECT COUNT(*) FROM partida WHERE mazo_id = :mazo_id AND estado = 'en_curso' ";
        $pdo= $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':mazo_id', $mazoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function mazoServidorEnUso(int $mazoId): bool
    {
        $query = "SELECT COUNT(*) FROM mazo_carta WHERE mazo_id = :mazo_id AND estado = 'en_mano' ";
        $pdo= $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':mazo_id', $mazoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function resultadoUsuario(string $rto, int $partidaId): void
    {
        $query= "UPDATE partida SET el_usuario = :rto WHERE id = :partidaId";
        $pdo= $this->database->getConnection();
        $stmt= $pdo->prepare($query);
        $stmt->bindValue(':rto', $rto, PDO::PARAM_STR);
        $stmt->bindValue(':partidaId', $partidaId, PDO::PARAM_INT);
        $stmt->execute();
    }
}
