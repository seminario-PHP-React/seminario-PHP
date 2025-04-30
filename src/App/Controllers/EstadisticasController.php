<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Model\EstadisticaModel;


class EstadisticasController{
    public function __construct(private EstadisticaModel $model)
    {
        
    }
    public function show(Request $request, Response $response): Response
    {
        $data = $this->model->getEstadistica();
        if(!isset($data)){
            $response->getBody()->write(json_encode(['Mensaje' => 'No hay estadisticas disponibles'] ));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
        $payload= [];
        foreach ($data as $d){
            $payload[] = [
                'Nombre' => $d['nombre'],
                'Partidas jugadas' => $d['total_jugadas'],
                'Victorias' => $d['victorias'],
                'Empates' => $d['empates'],
                'Derrotas' => $d['derrotas']
            ];

        };

        $response->getBody()->write(json_encode($payload) );
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        
    }
}