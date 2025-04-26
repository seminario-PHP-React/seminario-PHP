<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Model\PartidaModel;

class PartidaController {
    public function __construct(private PartidaModel $model) {}

    public function start(Request $request, Response $response): Response {
        $user = $request->getAttribute('usuario');
        $data = $request->getParsedBody(); 
    
        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'No autorizado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    
        if (!isset($data['mazo'])) {
            $response->getBody()->write(json_encode(['error' => 'Ingrese el ID del mazo seleccionado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    
       
        $mazo = $this->model->find($data['mazo']);

        if (!$mazo || !isset($mazo['usuario_id']) || $mazo['usuario_id'] != $user['id']) {
            
            $response->getBody()->write(json_encode(['error' => 'Mazo no válido o no pertenece al usuario']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
    
        $partidaData = [
            'user_id' => $user['id'],
            'date' => date('Y-m-d H:i:s'),
            'mazo_id' => $data['mazo'], 
            'state' => 'en_curso'
        ];
    

        if (!isset($partidaData['user_id'], $partidaData['date'], $partidaData['mazo_id'], $partidaData['state'])) {
            $response->getBody()->write(json_encode('Falta contenido ')); 
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);  
        }
        
        $id = $this->model->create($partidaData); 
        
        $cartas = $this->model->getCartas(2); 
        
        $payload = [
            'partida_id' => $id,
            'cartas' => $cartas
        ];
    
        $response->getBody()->write(json_encode($payload)); 
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
        
    }
    
    
}
