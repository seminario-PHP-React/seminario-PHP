<?php
declare(strict_types=1);

namespace App\Model;

use App\Database;
use PDO;
use Exception;

class JugadaModel
{
    public function __construct(private Database $database) {}

    public function insertarJugada(int $partidaId, int $cartaIdUsuario, int $cartaIdServidor, int $mazoServidor): array
    {
        $pdo = $this->database->getConnection();
        $sql = 'INSERT INTO jugada (partida_id, carta_id_a, carta_id_b)
                VALUES (:partidaId, :cartaUsuario, :cartaServidor)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':partidaId'     => $partidaId,
            ':cartaUsuario'  => $cartaIdUsuario,
            ':cartaServidor' => $cartaIdServidor,
        ]);

        $nuevoId = (int)$pdo->lastInsertId();
        $this->marcarCartaJugadaUsuario($cartaIdUsuario, $partidaId);
        $this->marcarCartaJugadaServidor($cartaIdServidor, $mazoServidor);

        return $this->obtenerJugadaPorId($nuevoId);
    }

    public function marcarCartaJugadaUsuario(int $carta_id, int $partida_id): void
    {
        $sql = 'UPDATE mazo_carta MC
                JOIN mazo M ON MC.mazo_id = M.id
                JOIN partida P ON M.id = P.mazo_id
                SET MC.estado = "descartado"
                WHERE MC.carta_id = :carta_id AND P.id = :partida_id';
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute([
            ':carta_id'    => $carta_id,
            ':partida_id'  => $partida_id
        ]);
    }

    public function marcarCartaJugadaServidor(int $carta_id, int $mazo_id): void
    {
        $sql = 'UPDATE mazo_carta
                SET estado = "descartado"
                WHERE carta_id = :carta_id AND mazo_id = :mazo_id';
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute([
            ':carta_id' => $carta_id,
            ':mazo_id'  => $mazo_id
        ]);
    }

    public function marcarResultado(string $rto, int $jugadaId): void
    {
        $sql = 'UPDATE jugada SET el_usuario = :rto WHERE id = :jugada';
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute([
            ':rto'    => $rto,
            ':jugada' => $jugadaId
        ]);
    }

    public function obtenerJugadaPorId(int $idJugada): array
    {
        $sql = 'SELECT * FROM jugada WHERE id = :idJugada';
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute([':idJugada' => $idJugada]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function determinarGanadorRonda(array $registroJugada): array
    {
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare('SELECT id, ataque, atributo_id FROM carta WHERE id IN (:a, :b)');
        $stmt->execute([
            ':a' => $registroJugada['carta_id_a'],
            ':b' => $registroJugada['carta_id_b']
        ]);
        $cartas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $usuario = null;
        $servidor = null;

        foreach ($cartas as $carta) {
            if ($carta['id'] === (int)$registroJugada['carta_id_a']) {
                $usuario = $carta;
            }
            if ($carta['id'] === (int)$registroJugada['carta_id_b']) {
                $servidor = $carta;
            }
        }

        if ($registroJugada['carta_id_a'] === $registroJugada['carta_id_b']) {
            if ($usuario !== null) {
                $servidor = $usuario;
            }
        }

        if ($usuario === null || $servidor === null) {
            throw new Exception('Faltan datos de carta para determinar el ganador.');
        }

        $ataqueUsuario  = (float)$usuario['ataque'];
        $ataqueServidor = (float)$servidor['ataque'];

        $stmtVentaja = $pdo->prepare('SELECT COUNT(*) FROM gana_a WHERE atributo_id = :a AND atributo_id2 = :b');

        $stmtVentaja->execute([
            ':a' => $usuario['atributo_id'],
            ':b' => $servidor['atributo_id']
        ]);
        $ventajaUsuario = (int)$stmtVentaja->fetchColumn();

        $stmtVentaja->execute([
            ':a' => $servidor['atributo_id'],
            ':b' => $usuario['atributo_id']
        ]);
        $ventajaServidor = (int)$stmtVentaja->fetchColumn();

        if ($ventajaUsuario > 0)  $ataqueUsuario  *= 1.3;
        if ($ventajaServidor > 0) $ataqueServidor *= 1.3;

        $resultado = 'empato';
        if ($ataqueUsuario > $ataqueServidor) {
            $resultado = 'gano';
        } elseif ($ataqueServidor > $ataqueUsuario) {
            $resultado = 'perdio';
        }

        return [
            'resultado' => $resultado,
            'fuerza_usuario' => $ataqueUsuario,
            'fuerza_servidor' => $ataqueServidor
        ];
    }


    public function cantidadDeRondas(int $partidaId): int
    {
        $sql = 'SELECT COUNT(*) FROM jugada WHERE partida_id = :id';
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute([':id' => $partidaId]);
        return (int)$stmt->fetchColumn();
    }

    public function obtenerCartasDisponibles(int $mazoId): array
    {
        $sql = 'SELECT carta_id FROM mazo_carta WHERE mazo_id = :mazoId AND estado = "en_mano"';
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute([':mazoId' => $mazoId]);
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_column($filas, 'carta_id');
    }

    public function obtenerJugadasDePartida(int $partidaId): array
    {
        $sql = 'SELECT * FROM jugada WHERE partida_id = :partidaId';
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute([':partidaId' => $partidaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(int $partidaId, int $cartaId, string $estado): int {
        $pdo = $this->database->getConnection();
        $pdo->beginTransaction();

        try {
            $query = "INSERT INTO jugada (partida_id, carta_id, estado) VALUES (:partida_id, :carta_id, :estado)";
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(':partida_id', $partidaId, PDO::PARAM_INT);
            $stmt->bindValue(':carta_id', $cartaId, PDO::PARAM_INT);
            $stmt->bindValue(':estado', $estado, PDO::PARAM_STR);
            $stmt->execute();

            $jugadaId = (int)$pdo->lastInsertId();
            $pdo->commit();
            $this->database->closeConnection();
            return $jugadaId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            $this->database->closeConnection();
            throw $e;
        }
    }

    public function getJugadasPartida(int $partidaId): array {
        $pdo = $this->database->getConnection();
        $query = "
            SELECT j.*, c.nombre as carta_nombre, c.poder as carta_poder 
            FROM jugada j 
            JOIN carta c ON j.carta_id = c.id 
            WHERE j.partida_id = :partida_id 
            ORDER BY j.fecha_creacion ASC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':partida_id', $partidaId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $result;
    }

    public function getUltimaJugada(int $partidaId): ?array {
        $pdo = $this->database->getConnection();
        $query = "
            SELECT j.*, c.nombre as carta_nombre, c.poder as carta_poder 
            FROM jugada j 
            JOIN carta c ON j.carta_id = c.id 
            WHERE j.partida_id = :partida_id 
            ORDER BY j.fecha_creacion DESC 
            LIMIT 1
        ";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':partida_id', $partidaId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $result ?: null;
    }

    public function getCartasJugadas(int $partidaId): array {
        $pdo = $this->database->getConnection();
        $query = "
            SELECT c.id, c.nombre, c.poder 
            FROM jugada j 
            JOIN carta c ON j.carta_id = c.id 
            WHERE j.partida_id = :partida_id 
            ORDER BY j.fecha_creacion ASC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':partida_id', $partidaId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $result;
    }

    public function getCartasEnMano(int $partidaId): array {
        $pdo = $this->database->getConnection();
        $query = "
            SELECT c.id, c.nombre, c.poder 
            FROM mazo_carta mc 
            JOIN carta c ON mc.carta_id = c.id 
            WHERE mc.mazo_id = (
                SELECT mazo_id FROM partida WHERE id = :partida_id
            ) 
            AND mc.estado = 'en_mano'
        ";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':partida_id', $partidaId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->database->closeConnection();
        return $result;
    }

    public function actualizarEstadoCarta(int $partidaId, int $cartaId, string $estado): void {
        $pdo = $this->database->getConnection();
        $query = "
            UPDATE mazo_carta 
            SET estado = :estado 
            WHERE mazo_id = (
                SELECT mazo_id FROM partida WHERE id = :partida_id
            ) 
            AND carta_id = :carta_id
        ";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':estado', $estado, PDO::PARAM_STR);
        $stmt->bindValue(':partida_id', $partidaId, PDO::PARAM_INT);
        $stmt->bindValue(':carta_id', $cartaId, PDO::PARAM_INT);
        $stmt->execute();
        $this->database->closeConnection();
    }

    public function cartaEnMano(int $partidaId, int $cartaId): bool {
        $pdo = $this->database->getConnection();
        $query = "
            SELECT COUNT(*) 
            FROM mazo_carta 
            WHERE mazo_id = (
                SELECT mazo_id FROM partida WHERE id = :partida_id
            ) 
            AND carta_id = :carta_id 
            AND estado = 'en_mano'
        ";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':partida_id', $partidaId, PDO::PARAM_INT);
        $stmt->bindValue(':carta_id', $cartaId, PDO::PARAM_INT);
        $stmt->execute();
        $result = (bool)$stmt->fetchColumn();
        $this->database->closeConnection();
        return $result;
    }

    public function getPoderCarta(int $cartaId): int {
        $pdo = $this->database->getConnection();
        $query = "SELECT poder FROM carta WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':id', $cartaId, PDO::PARAM_INT);
        $stmt->execute();
        $result = (int)$stmt->fetchColumn();
        $this->database->closeConnection();
        return $result;
    }

    public function getPoderTotalJugadas(int $partidaId): int {
        $pdo = $this->database->getConnection();
        $query = "
            SELECT SUM(c.poder) 
            FROM jugada j 
            JOIN carta c ON j.carta_id = c.id 
            WHERE j.partida_id = :partida_id
        ";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':partida_id', $partidaId, PDO::PARAM_INT);
        $stmt->execute();
        $result = (int)$stmt->fetchColumn();
        $this->database->closeConnection();
        return $result;
    }
}
