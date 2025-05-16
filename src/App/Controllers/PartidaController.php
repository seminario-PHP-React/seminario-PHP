<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Model\PartidaModel;

class PartidaController {
    public function __construct(private PartidaModel $model) {}

    public function start(Request $request, Response $response): Response {
        try {
            $usuarioId = $request->getAttribute('usuario_id');
            $data = $request->getParsedBody(); 

            if (!$usuarioId) {
                $payload = ['Mensaje' => 'No autorizado'];
                $response->getBody()->write(json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            if (!isset($data['mazo'])) {
                $payload = ['Mensaje' => 'Ingrese el ID del mazo seleccionado'];
                $response->getBody()->write(json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $mazoId = $data['mazo'];

            $mazo = $this->model->findMazoByIdAndUser((int)$mazoId, (int)$usuarioId);

            if (!$mazo) {
                $payload = ['Mensaje' => 'Mazo no válido o no pertenece al usuario'];
                $response->getBody()->write(json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
            
            if ($this->model->mazoServidorEnUso(1)) {
                $payload = ['Mensaje' => 'El servidor ya se encuentra jugando otra partida'];
                $response->getBody()->write(json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            if ($this->model->mazoEnUso($mazoId)) {
                $payload = ['Mensaje' => 'Este mazo ya está siendo utilizado en otra partida en curso, por favor seleccione otro'];
                $response->getBody()->write(json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }
            
            $partidaData = [
                'user_id' => $usuarioId,
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
            $payload = ['Mensaje' => 'Error en el servidor', 'Error' => $e->getMessage()];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    public function cartasEnMano(Request $request, Response $response, string $usuario, string $partida): Response {
        try {
            $usuarioId = $request->getAttribute('usuario_id');
        
            if ($usuario !== (string)$usuarioId) {
                $payload = ['Mensaje' => 'El ID de usuario proporcionado no coincide con el usuario autenticado.'];
                $response->getBody()->write(json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
        
            $mazo = $this->model->getMazoPorPartida($partida, $usuario); 
        
            if (!$mazo) {
                $payload = ['Mensaje' => 'Esta partida no existe o no pertenece a este usuario'];
                $response->getBody()->write(json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
        
            if ($mazo['estado'] !== 'en_curso') {
                $payload = ['Mensaje' => 'La partida no se encuentra en curso'];
                $response->getBody()->write(json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }
    
            $cartasRestantes = $this->model->getCartasMano($usuario, $partida);
        
            $payload = [];
            foreach ($cartasRestantes as $carta) {
                $payload[] = [
                    'ID carta' => $carta['carta_id'],
                    'Nombre del pokemon' => $carta['nombre'],
                    'Nombre del ataque' => $carta['ataque_nombre']
                ];
            }
        
            $response->getBody()->write(json_encode($payload)); 
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    
        } catch (\Throwable $e) {
            $payload = ['Mensaje' => 'Error en el servidor', 'Error' => $e->getMessage()];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
