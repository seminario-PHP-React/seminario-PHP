<?php
namespace App\Model;

use App\Database;

class MazoModel {
    private \PDO $db; // variable para conectarse a bd

    public function __construct(Database $database) {
        $this->db = $database->getConnection(); 
    }

    public function getUserMazos(int $usuario_id): array {
        $stmt = $this->db->prepare("SELECT id, nombre FROM mazo WHERE usuario_id = :usuario_id"); 
        $stmt->execute(['usuario_id' => $usuario_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC); // devuelve un array asociativo
    }

    // chequeamos si el mazo es del usuario
    public function mazoPerteneceAUsuario(int $mazoId, int $usuarioId): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM mazo WHERE id = :mazoId AND usuario_id = :usuarioId");
        $stmt->execute([
            'mazoId' => $mazoId,
            'usuarioId' => $usuarioId
        ]);
        return (bool) $stmt->fetchColumn();
    }
        
    //se tienen que eliminar las cartas asociadas a ese mazo y el mazo
    public function eliminarMazoConCartas(int $mazoId): int {
       
        // Verifica si el mazo participo en una partida
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM partida 
            WHERE mazo_id = :mazoId
        ");
        $stmt->execute(['mazoId' => $mazoId]);

        if ((int)$stmt->fetchColumn() > 0) {
            throw new \Exception("El mazo ha participado de una partida y no puede ser eliminado");
        }

        // sino, eliminamos primero las cartas y luego el mazo
        $this->db->beginTransaction(); // se hace una transaccion para evitar que se borren algunas cosas si y otras no
        try {
            // elimina las cartas del mazo
            $stmtCartas = $this->db->prepare("DELETE FROM mazo_carta WHERE mazo_id = :mazoId");
            $stmtCartas->execute(['mazoId' => $mazoId]);
    
            // elimina el mazo
            $stmtMazo = $this->db->prepare("DELETE FROM mazo WHERE id = :mazoId");
            $stmtMazo->execute(['mazoId' => $mazoId]);
    
            $this->db->commit();
            return $stmtMazo->rowCount(); // 1 si elimino el mazo correctamente
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
}
