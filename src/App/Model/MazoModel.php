<?php
namespace App\Model;

use App\Database;

class MazoModel {
    private \PDO $db; // variable para conectarse a bd

    public function __construct(Database $database) {
        $this->db = $database->getConnection(); 
    }

    public function existenCartas(array $cartas): bool {
        if (empty($cartas)) {
            return false;
        }

        $placeholders =  implode(',', array_fill(0, count($cartas), '?'));
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM carta WHERE id IN ($placeholders)");
        $stmt->execute($cartas);
        return ((int)$stmt->fetchColumn()) === count($cartas);
    }

    public function cantidadMazosUsuario(int $usuario_id): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM mazo WHERE usuario_id = :usuario_id");
        $stmt->execute(['usuario_id' => $usuario_id]);
        return (int)$stmt->fetchColumn();
    }
    

    public function crearMazo(int $usuarioId, string $nombre, array $cartas): int {
        $this->db->beginTransaction(); 
        try {
            // inserto mazo
            $stmtMazo = $this->db->prepare("INSERT INTO mazo (usuario_id, nombre) VALUES (:usuario_id, :nombre)");
            $stmtMazo->execute([
                'usuario_id' => $usuarioId, 
                'nombre' => $nombre
            ]);
            $mazoId = (int)$this->db->lastInsertId(); // genera el id de manera autoincremental
            
            $stmtCarta = $this->db->prepare("INSERT INTO mazo_carta (mazo_id, carta_id, estado) VALUES (:mazo_id, :carta_id, 'en_mazo')");
            
            // por cada carta en el array inserto una fila
            foreach ($cartas as $cartaId) { 
                $stmtCarta->execute([
                    'mazo_id' => $mazoId,
                    'carta_id' => $cartaId
                ]);
            }

            $this->db->commit();
            return $mazoId;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }


    }

    public function nombreMazoExiste(int $usuarioId, string $nombre, int $mazoIdActual): bool {
        // verifica si existe el nombre del mazo 
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM mazo 
            WHERE usuario_id = :usuario_id AND nombre = :nombre AND id != :mazo_id
        ");

        $stmt->execute([
            'usuario_id' => $usuarioId,
            'nombre' => $nombre,
            'mazo_id' => $mazoIdActual
        ]);

        return (bool) $stmt->fetchColumn();
    }
    

    public function editarNombreMazo(int $mazoId, string $nuevoNombre): void {
        $stmt = $this->db->prepare("UPDATE mazo SET nombre = :nombre WHERE id = :id");
        $stmt->execute([
            'nombre' => $nuevoNombre,
            'id' => $mazoId
        ]);
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
