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
    public function __construct(private CardModel $model, private Validator $validator) {
        
        $this->validator->mapFieldsRules
        ([
            'name' => ['required'],
            'attack' => ['required', 'integer',['min', 1]],
            'attack_name' => ['required'],
            'attribute_id' => ['required', 'integer',['min', 1]]
        ]);
    }
    
    public function show(Request $request, Response $response, string $id): Response
    {
        $Card = $request->getAttribute('Card');
        $body = json_encode(['Carta' => $Card]);
        $response->getBody()->write($body);
        return $response;
    }

    public function showByData(Request $request, Response $response, string $atributo, string $nombre): Response 
    {
        // Normalización
        $atributo = ucfirst(strtolower($atributo));
        $nombre = ucfirst(strtolower($nombre));

        $rows = $this->model->getCardByData($atributo, $nombre);
        if (empty($rows)) {
            $response->getBody()->write(json_encode([
                'Mensaje' => 'Carta no encontrada con ese atributo y nombre.'
            ])); 
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    
        $response->getBody()->write(json_encode($rows));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    



    public function create(Request $request, Response $response): Response{
        $body =  $request->getParsedBody();

        $this->validator = $this->validator->withData($body);

        if(! $this-> validator->validate()){

            $response-> getBody()->write(json_encode($this->validator->errors()));

            return $response->withStatus(422);
        }
        $id = $this->model->create($body);
        $body = json_encode([
            'Mensaje' => 'Carta creada con éxito',
            'ID' => $id

        ]);
        $response->getBody()->write($body);
        return $response->withStatus(201);
    }
    public function update(Request $request, Response $response, string $id): Response{
        $body =  $request->getParsedBody();

        $this->validator = $this->validator->withData($body);

        if(! $this-> validator->validate()){

            $response-> getBody()->write(json_encode($this->validator->errors()));

            return $response->withStatus(422);
        }
        $rows = $this->model->update((int) $id, $body);
        $body = json_encode([
            'Mensaje' => 'Carta actualizada con éxito',
            'Filas' => $rows

        ]);
        $response->getBody()->write($body);
        return $response;
    }
    public function delete(Request $request, Response $response, string $id): Response{
        $rows = $this->model->delete($id);
        $body = json_encode([
            'Mensaje' => 'Carta eliminada',
            'Filas'=> $rows
        ]);
        $response->getBody()->write($body);
        return $response;
        
    }
}