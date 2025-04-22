<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Model\MazoModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MazoController {

    public function __construct(private MazoModel $model) {
    }

       
    public function getUserMazos(Request $request, Response $response, string $usuario_id): Response {
        $usuario = $request->getAttribute('usuario');

        // Valida que el usuario consultado sea el mismo que esta logueado
        if ((int)$usuario_id !== (int)$usuario['id']) {
            $response->getBody()->write(json_encode(["error" => "No autorizado"]));
            return $response->withStatus(401);
        }

        $mazos = $this->model->getUserMazos($usuario['id']);
        $response->getBody()->write(json_encode($mazos));
        return $response->withHeader("Content-Type", "application/json");
    }

    public function delete(Request $request, Response $response, string $id): Response {
        $usuario = $request->getAttribute('usuario'); // usuario logueado
    
        // validamos que el mazo sea del usuario 
        if (! $this->model->mazoPerteneceAUsuario((int) $id, (int) $usuario['id'])) {
            $response->getBody()->write(json_encode(["error" => "No autorizado para eliminar este mazo"]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    
        try {
            // intenta eliminar mazo --> si falla continua con el catch
            $this->model->eliminarMazoConCartas((int)$id);
    
            // si se pudo eliminar
            $response->getBody()->write(json_encode(["message" => "Mazo eliminado"]));
            return $response->withStatus(200);
    
        } catch (\Exception $e) {
            // si el error fue porq participo en una partida
            if ($e->getMessage() === "El mazo ha participado de una partida y no puede ser eliminado") {
                $response->getBody()->write(json_encode(["error" => $e->getMessage()]));
                return $response->withStatus(409);
            }
    
            // otros errores
            //$response->getBody()->write(json_encode(["error" => "Error interno del servidor"]));
            $response->getBody()->write(json_encode([
                "error" => "Error interno del servidor",
                "mensaje" => $e->getMessage(),
                "linea" => $e->getLine(),
                "archivo" => $e->getFile()
            ]));
            
            return $response->withStatus(500);

        }
    }

    public function create(Request $request, Response $response): Response {
        $usuario = $request->getAttribute('usuario');
        $data = $request->getParsedBody();
    
        // Si no existe nombre o array de cartas --> faltan datos
        if (!isset($data['nombre'], $data['cartas']) || !is_array($data['cartas'])) {
            $response->getBody()->write(json_encode(["error" => "Faltan datos requeridos (nombre o cartas)"]));
            return $response->withStatus(400)->withHeader("Content-Type", "application/json");
        }
    
        $nombre = trim($data['nombre']);    // elimina espacios en blanco
        $cartas = $data['cartas'];          
    
        switch (true) {
            case count($cartas) > 5:
                $mensaje = "Máximo 5 cartas por mazo";
                break;
        
            case count($cartas) !== count(array_unique($cartas)):
                $mensaje = "No se permiten cartas repetidas";
                break;
        
            case ! $this->model->existenCartas($cartas):
                $mensaje = "Una o más cartas no existen";
                break;
        
            case $this->model->cantidadMazosUsuario($usuario['id']) == 3:
                $mensaje = "Jugador con 3 mazos";
                break;
        }
        
        if (isset($mensaje)) {
            $response->getBody()->write(json_encode(["error" => $mensaje]));
            return $response->withStatus(400)->withHeader("Content-Type", "application/json");
        }
            
    
        // Intentar crear el mazo
        try {
            $mazoId = $this->model->crearMazo($usuario['id'], $nombre, $cartas);
    
            $response->getBody()->write(json_encode([
                "id" => $mazoId,
                "nombre" => $nombre
            ]));
            return $response->withStatus(201)->withHeader("Content-Type", "application/json");
    
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                "error" => "Error al crear el mazo",
                "mensaje" => $e->getMessage()
            ]));
            return $response->withStatus(400)->withHeader("Content-Type", "application/json");
        }
    }
    
    


}
