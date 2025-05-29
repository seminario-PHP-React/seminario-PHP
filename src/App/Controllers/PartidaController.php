<?php
declare(strict_types=1);

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
            
            $mazoId = (int)$data['mazo'];

            // Primero verificamos si el mazo pertenece al usuario
            $mazo = $this->model->findMazoByIdAndUser($mazoId, (int)$user);
            if (!$mazo) {
                $response->getBody()->write(json_encode(['Mensaje' => 'Mazo no válido o no pertenece al usuario']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Luego verificamos si el mazo ya está en uso
            if ($this->model->mazoEnUso($mazoId, (int)$user)) {
                $response->getBody()->write(json_encode(['Mensaje' => 'Este mazo ya está siendo utilizado en otra partida en curso, por favor seleccione otro']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            // Finalmente verificamos si el servidor está ocupado
            if ($this->model->mazoServidorEnUso(1)) {
                $response->getBody()->write(json_encode(['Mensaje' => 'El servidor ya se encuentra jugando otra partida']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }
            
            $partidaData = [
                'user_id' => (int)$user,
                'date' => date('Y-m-d H:i:s'),
                'mazo_id' => $mazoId,
                'state' => 'en_curso'
            ];
            
            $id = $this->model->create($partidaData); 
            $cartas = $this->model->getCartas($mazoId); 
            
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
        
            if ((int)$usuario != (int)$user) {
                $response->getBody()->write(json_encode(['Mensaje' => 'El ID de usuario proporcionado no coincide con el usuario autenticado.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
        
            $mazo = $this->model->getMazoPorPartida((int)$partida, (int)$usuario); 
        
            if (!$mazo) {
                $response->getBody()->write(json_encode(['Mensaje' => 'Esta partida no existe o no pertenece a este usuario']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
        
            if ($mazo['estado'] !== 'en_curso') {
                $response->getBody()->write(json_encode(['Mensaje' => 'La partida no se encuentra en curso']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
    
            $cartasRestantes = $this->model->getCartasMano((int)$usuario, (int)$partida);
        
            $payload = [];
        
            foreach ($cartasRestantes as $cartasR) {
                $payload[] = [
                    'ID carta' => $cartasR['carta_id'],
                    'Nombre del pokemon' => $cartasR['nombre'],
                    'Nombre del ataque' => $cartasR['ataque_nombre'],
                    'Ataque' => $cartasR['ataque']
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
