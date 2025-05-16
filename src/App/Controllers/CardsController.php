<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Model\CardModel;
use Exception;

class CardsController {
    public function __construct(private CardModel $model) {}

    public function showByData(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();

            $atributo = isset($params['atributo']) && $params['atributo'] !== '' 
                ? strtolower($params['atributo']) . '%' 
                : null;
            $nombre = isset($params['nombre']) && $params['nombre'] !== '' 
                ? strtolower($params['nombre']) . '%' 
                : null;

            $rows = $this->model->getCardByData($atributo, $nombre);

            if (empty($rows)) {
                $response->getBody()->write(json_encode([
                    'Mensaje' => 'Carta no encontrada con esos parámetros.'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $response->getBody()->write(json_encode($rows));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'Error' => 'Ocurrió un error inesperado.',
                'Detalle' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
