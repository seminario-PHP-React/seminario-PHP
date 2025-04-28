<?php


declare(strict_types=1);
namespace App\Model;

use App\Database;
use PDO;

class JugadaModel
{
    public function __construct(private Database $database) {}

   
    public function insertarJugada(int $partidaId, int $cartaIdUsuario, int $cartaIdServidor): array
    {
        $conexion = $this->database->getConnection();
        $sql = 'INSERT INTO jugada (partida_id, carta_id_a, carta_id_b)
                VALUES (:partidaId, :cartaUsuario, :cartaServidor)';
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':partidaId'     => $partidaId,
            ':cartaUsuario'  => $cartaIdUsuario,
            ':cartaServidor' => $cartaIdServidor,
        ]);

        $nuevoId = (int) $conexion->lastInsertId();
        $this->marcarCartaJugada($cartaIdUsuario, $partidaId);
        return $this->obtenerJugadaPorId($nuevoId);
    }

    public function marcarCartaJugada($carta_id, $partida_id){
        $query='UPDATE mazo_carta MC
        LEFT JOIN mazo M ON MC.mazo_id = M.id
        LEFT JOIN partida P ON M.id = P.mazo_id
        SET MC.estado = \'descartado\'
        WHERE MC.carta_id = :carta_id AND P.id = :partida_id';

        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':carta_id', $carta_id, PDO::PARAM_INT);
        $stmt->bindParam(':partida_id', $partida_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function marcarResultado($rto, $jugada){
        
        $query='UPDATE jugada J
            SET J.el_usuario = :rto
            WHERE J.id = :jugada';

         $pdo = $this->database->getConnection();
         $stmt = $pdo->prepare($query);
         $stmt->bindParam(':rto', $rto, PDO::PARAM_STR);
         $stmt->bindParam(':jugada', $jugada, PDO::PARAM_INT);
         $stmt->execute();
    }
   
    public function obtenerJugadaPorId(int $idJugada): array
    {
        $sql = 'SELECT * FROM jugada WHERE id = :idJugada';
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->execute([':idJugada' => $idJugada]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    
    public function determinarGanadorRonda(array $registroJugada): string
    {
        $conexion = $this->database->getConnection();

        // 1) Traer datos de ambas cartas
        $sqlCartas = 'SELECT id, ataque, atributo_id
                      FROM carta
                      WHERE id IN (:idUsuario, :idServidor)';
        $stmtCartas = $conexion->prepare($sqlCartas);
        $stmtCartas->execute([
            ':idUsuario'  => $registroJugada['carta_id_a'],
            ':idServidor' => $registroJugada['carta_id_b'],
        ]);
        $cartas = $stmtCartas->fetchAll(PDO::FETCH_ASSOC);
    
        // Asignar variables descriptivas
        foreach ($cartas as $carta) {
            if ($carta['id'] === (int)$registroJugada['carta_id_a']) {
                $cartaUsuario = $carta;
            } else {
                $cartaServidor = $carta;
            }
        }
    
        $ataqueUsuario  = (float) $cartaUsuario['ataque'];
        $ataqueServidor = (float) $cartaServidor['ataque'];
    
        // 2) Verificar ventaja de atributos
        $sqlVentajaUsuario = 'SELECT COUNT(*) AS total
                              FROM gana_a
                              WHERE atributo_id = :atributoA AND atributo_id2 = :atributoB';
        $sqlVentajaServidor = 'SELECT COUNT(*) AS total
                               FROM gana_a
                               WHERE atributo_id = :atributoA AND atributo_id2 = :atributoB';
        $stmtVentajaUsuario = $conexion->prepare($sqlVentajaUsuario);
        $stmtVentajaServidor = $conexion->prepare($sqlVentajaServidor);
    
        // Ventaja usuario sobre servidor
        $stmtVentajaUsuario->execute([
            ':atributoA' => $cartaUsuario['atributo_id'],
            ':atributoB' => $cartaServidor['atributo_id'],
        ]);
        $ventajaUsuario = (int)$stmtVentajaUsuario->fetch(PDO::FETCH_ASSOC)['total'];
    
        // Ventaja servidor sobre usuario
        $stmtVentajaServidor->execute([
            ':atributoA' => $cartaServidor['atributo_id'],
            ':atributoB' => $cartaUsuario['atributo_id'],
        ]);
        $ventajaServidor = (int)$stmtVentajaServidor->fetch(PDO::FETCH_ASSOC)['total'];
    
        // Si hay ventaja para el usuario, se aumenta el ataque
        if ($ventajaUsuario > 0) {
            $ataqueUsuario *= 1.3;
        }
    
        // Si hay ventaja para el servidor, se aumenta el ataque
        if ($ventajaServidor > 0) {
            $ataqueServidor *= 1.3;
        }
        
        if ($ventajaUsuario === 0 && $ventajaServidor === 0){
        $rto= 'empato';
        }
        elseif ($ataqueUsuario > $ataqueServidor) {
            $rto= 'gano';
        }
        elseif ($ataqueServidor > $ataqueUsuario) {
            $rto= 'perdio';
        }
        
       

        return $rto;
    }
    public function cantidadDeRondas(int $partidaId): int {
        $query = 'SELECT COUNT(p.id) AS total
                FROM partida p
                LEFT JOIN mazo_carta mc ON mc.mazo_id = p.mazo_id
                WHERE mc.estado = \'descartado\' AND p.id = :partidaId';
        
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':partidaId', $partidaId, PDO::PARAM_INT);
        $stmt->execute();
        
        
        $totalJugadas = $stmt->fetchColumn();
        
        return (int) $totalJugadas;  // Retornar el total de jugadas como entero
    }
    


}


