<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Model\PartidaModel;

class PartidaController {
    public function __construct(private PartidaModel $model) {}

    public function start(Request $request, Response $response): Response {
        
        $user = $request->getAttribute('usuario');

    
        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'No autorizado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    
       
    
        $partidaData = [
            'user_id' => $user['id'],
            'date' => date('Y-m-d H:i:s'),
            'mazo_id' => '1', // TODO mazo VA A SER UN RANDOM PERO FALTA HACER EL MODULO MAZO 
            'state' => 'en_curso'
        ];
    
        $id = $this->model->create($partidaData);
    
        $response->getBody()->write(json_encode(["Partida número" => $id])); // TODO Habria que chequear que no este jugando 30 partidas a la vez el usuario 
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
    
}
