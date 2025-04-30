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
    
        if ($usuario_id != $usuario['id']) {
            $response->getBody()->write(json_encode(["Mensaje" => "No autorizado"]));
            return $response->withHeader("Content-Type", "application/json")
                            ->withStatus(401);
        }
    
        $mazos = $this->model->getUserMazos((int) $usuario_id);
        $response->getBody()->write(json_encode($mazos));
        return $response->withHeader("Content-Type", "application/json")
                        ->withStatus(200);
    }
    

    // Eliminar mazo
    public function delete(Request $request, Response $response, string $id): Response {
        $usuario = $request->getAttribute('usuario'); // usuario logueado
        
        if (! $this->model->mazoExiste((int) $usuario['id'], (int) $id )) {
            $response->getBody()->write(json_encode(["Mensaje" => "Este mazo no existe o no pertenece al usuario"]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // validamos que el mazo sea del usuario 
        
    
        try {
            // intenta eliminar mazo --> si falla continua con el catch
            $this->model->eliminarMazoConCartas((int)$id);
    
            // si se pudo eliminar
            $response->getBody()->write(json_encode(["Mensaje" => "Mazo eliminado"]));
            return $response->withStatus(200);
    
        } catch (\Exception $e) {
            // si el error fue porq participo en una partida
            if ($e->getMessage() === "El mazo ha participado de una partida y no puede ser eliminado") {
                $response->getBody()->write(json_encode(["Mensaje" => $e->getMessage()]));
                return $response->withStatus(409);
            }
    
            // otros errores
            //$response->getBody()->write(json_encode(["error" => "Error interno del servidor"]));
            $response->getBody()->write(json_encode([
                "Error" => "Error interno del servidor",
                "Mensaje" => $e->getMessage(),
                "Linea" => $e->getLine(),
                "Archivo" => $e->getFile()
            ]));
            
            return $response->withStatus(500);

        }
    }

    // Crear nuevo mazo
    public function create(Request $request, Response $response): Response {
        $usuario = $request->getAttribute('usuario');
        $data = $request->getParsedBody();
    
       
        if (!isset($data['nombre'], $data['cartas']) || !is_array($data['cartas'])) {
            $response->getBody()->write(json_encode(["Mensaje" => "Los campos nombre o cartas son obligatorios"]));
            return $response->withStatus(400)->withHeader("Content-Type", "application/json");
        }
    
        $nombre = trim($data['nombre']);    // elimina espacios en blanco
        $cartas = $data['cartas'];   

        if ($this->model->nombreNuevoMazoExiste($usuario['id'], $nombre)) {
            $response->getBody()->write(json_encode(["Mensaje" => "Ya existe un mazo con ese nombre"]));
            return $response->withStatus(409)->withHeader("Content-Type", "application/json");
        }       
    
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
            $response->getBody()->write(json_encode(["Mensaje" => $mensaje]));
            return $response->withStatus(400)->withHeader("Content-Type", "application/json");
        }
            
    
        // Intentar crear el mazo
        try {
            $mazoId = $this->model->crearMazo($usuario['id'], $nombre, $cartas);
    
            $response->getBody()->write(json_encode([
                "ID" => $mazoId,
                "Nombre" => $nombre
            ]));
            return $response->withStatus(201)->withHeader("Content-Type", "application/json");
    
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                "Error" => "Error al crear el mazo",
                "Mensaje" => $e->getMessage()
            ]));
            return $response->withStatus(400)->withHeader("Content-Type", "application/json");
        }
    }
    
    // Editar nombre del mazo
    public function update(Request $request, Response $response, string $id): Response {
        $usuario = $request->getAttribute('usuario');
        $data = $request->getParsedBody();
    
        // verifica recepcion de parametros
        if (!isset($data['nombre']) || trim($data['nombre']) === '') {
            $response->getBody()->write(json_encode(["Mensaje" => "Nombre es un campo requerido"]));
            return $response->withStatus(400)->withHeader("Content-Type", "application/json");
        }
    
        $nuevoNombre = trim($data['nombre']); // elimina espacios
        
      

        if (! $this->model->mazoPerteneceAUsuario((int)$id, (int)$usuario['id'])) {
            $response->getBody()->write(json_encode(["Mensaje" => "No autorizado para editar este mazo"]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
          // verifica que no exista otro mazo con ese nombre
          if ($this->model->nombreMazoExiste($usuario['id'], $nuevoNombre, (int)$id)) {
            $response->getBody()->write(json_encode(["Mensaje" => "Ya existe un mazo con ese nombre"]));
            return $response->withStatus(409)->withHeader("Content-Type", "application/json");
        }
    
    
        try {
            $this->model->editarNombreMazo((int)$id, $nuevoNombre);
    
            $response->getBody()->write(json_encode(["Mensaje" => "Nombre actualizado"]));
            return $response->withStatus(200)->withHeader("Content-Type", "application/json");
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                "Error" => "Error al actualizar el nombre",
                "Mensaje" => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    }
    


}
