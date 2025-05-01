<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Model\PartidaModel;


class PartidaController {
    public function __construct(private PartidaModel $model) {}

    public function start(Request $request, Response $response): Response {
        try {
            $user = $request->getAttribute('user_id');
            $data = $request->getParsedBody(); 
        
            if (!$user) {
                $response->getBody()->write(json_encode(['Mensaje' => 'No autorizado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }
        
            if (!isset($data['mazo'])) {
                $response->getBody()->write(json_encode(['Mensaje' => 'Ingrese el ID del mazo seleccionado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $mazoId = $data['mazo'] ?? null;

            if (!$mazoId) {
                $response->getBody()->write(json_encode(['Mensaje' => 'Ingrese el ID del mazo seleccionado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $mazo = $this->model->findMazoByIdAndUser((int)$mazoId, (int)$user);

            if (!$mazo) {
                $response->getBody()->write(json_encode(['Mensaje' => 'Mazo no válido o no pertenece al usuario']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
            
            if ($this->model->mazoServidorEnUso(1)) {
                $response->getBody()->write(json_encode(['Mensaje' => 'El servidor ya se encuentra jugando otra partida']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409); // 409 Conflict
            }

            if ($this->model->mazoEnUso($mazoId)) {
                $response->getBody()->write(json_encode(['Mensaje' => 'Este mazo ya está siendo utilizado en otra partida en curso, por favor seleccione otro']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409); // 409 Conflict
            }

            
            
            $partidaData = [
                'user_id' => $user,
                'date' => date('Y-m-d H:i:s'),
                'mazo_id' => $data['mazo'], 
                'state' => 'en_curso'
            ];
        
            if (!isset($partidaData['user_id'], $partidaData['date'], $partidaData['mazo_id'], $partidaData['state'])) {
                $response->getBody()->write(json_encode(['Mensaje'=>'Falta contenido '])); 
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);  
            }
            
            $id = $this->model->create($partidaData); 
            $cartas = $this->model->getCartas($data['mazo']); 
            
            $payload = [
                'partida_id' => $id,
                'cartas' => $cartas
            ];
        
            $response->getBody()->write(json_encode($payload)); 
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode([
                'Mensaje' => 'Error en el servidor',
                'Error' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function cartasEnMano(Request $request, Response $response, string $usuario, string $partida): Response {
        try {
            $user = $request->getAttribute('user_id');
        
            if ($usuario != $user) {
                $response->getBody()->write(json_encode(['Mensaje' => 'El ID de usuario proporcionado no coincide con el usuario autenticado.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
        
            $mazo = $this->model->getMazoPorPartida($partida, $usuario); 
        
            if (!$mazo) {
                $response->getBody()->write(json_encode(['Mensaje' => 'Esta partida no existe o no pertenece a este usuario']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
        
            if ($mazo['estado'] !== 'en_curso') {
                $response->getBody()->write(json_encode(['Mensaje' => 'La partida no se encuentra en curso']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
    
            $cartasRestantes = $this->model->getCartasMano($usuario, $partida);
        
            $payload = [];
        
            foreach ($cartasRestantes as $cartasR) {
                $payload[] = [
                    'ID carta' => $cartasR['carta_id'],
                    'Nombre del pokemon' => $cartasR['nombre'],
                    'Nombre del ataque' => $cartasR['ataque_nombre']
                ];
            }
        
            $response->getBody()->write(json_encode($payload)); 
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode([
                'Mensaje' => 'Error en el servidor',
                'Error' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    
}
