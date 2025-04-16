<?php
namespace App\Repositories;

use App\Database;

class MazoRepository {
    private \PDO $db; // variable para conectarse a bd

    public function __construct(Database $database) {
        $this->db = $database->getConnection(); 
    }
    

    public function getUserMazos(int $usuario_id): array {
        $stmt = $this->db->prepare("SELECT id, nombre FROM mazo WHERE usuario_id = :usuario_id"); 
        $stmt->execute(['usuario_id' => $usuario_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC); // devuelve un array asociativo
    }
}
