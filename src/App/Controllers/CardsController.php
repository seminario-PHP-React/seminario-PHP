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
            $atributo = isset($params['atributo']) && $params['atributo'] !== '' ? strtolower($params['atributo']) . '%' : null;
            $nombre = isset($params['nombre']) && $params['nombre'] !== '' ? strtolower($params['nombre']) . '%' : null;

            $rows = $this->model->getCardByData($atributo, $nombre);

            if (empty($rows)) {
                $mensaje = 'No se encontraron cartas';
                if ($atributo) {
                    $mensaje .= " con el atributo '" . trim($atributo, '%') . "'";
                }
                if ($nombre) {
                    $mensaje .= " y nombre '" . trim($nombre, '%') . "'";
                }
                $mensaje .= '. Por favor, intente con otros criterios de búsqueda.';

                $response->getBody()->write(json_encode([
                    'Mensaje' => $mensaje,
                    'Sugerencia' => 'Puede intentar con otros atributos o nombres de cartas.'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $cartas = array_map(function($row) {
                return [
                    'ID' => $row['id'],
                    'Nombre' => $row['nombre'],
                    'Atributo' => $row['atributo'],
                    'Ataque' => $row['ataque'],
                    'Nombre del Ataque' => $row['ataque_nombre'],
                    'Imagen' => $row['imagen']
                ];
            }, $rows);

            $response->getBody()->write(json_encode([
                'Mensaje' => 'Cartas encontradas exitosamente',
                'Cantidad' => count($cartas),
                'Cartas' => $cartas
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'Mensaje' => 'Ocurrió un error al buscar las cartas',
                'Detalle' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
