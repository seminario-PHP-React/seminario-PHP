<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Model\CardModel;
use Valitron\Validator;

use function DI\string;
Validator::langDir(__DIR__.'/../../../vendor/vlucas/valitron/lang');
Validator::lang('es');

class CardsController{
    public function __construct(private CardModel $model) {
        
    }

    public function showByData(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $atributo = strtolower($params['atributo'] ?? '');
        $nombre = strtolower($params['nombre'] ?? '');
        $atributo = isset($params['atributo']) && $params['atributo'] !== '' ? strtolower($params['atributo']) . '%' : null;
        $nombre = isset($params['nombre']) && $params['nombre'] !== '' ? strtolower($params['nombre']) . '%' : null;

        
        $rows = $this->model->getCardByData($atributo, $nombre);

        if (empty($rows)) {
            $response->getBody()->write(json_encode([
                'Mensaje' => 'Carta no encontrada con esos parámetros.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode($rows));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }


}