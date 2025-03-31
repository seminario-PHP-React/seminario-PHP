<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\CardRepository;
use Valitron\Validator;

use function DI\string;

class Cards{
    public function __construct(private CardRepository $repository, private Validator $validator) {
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
        $body = json_encode($Card);
        $response->getBody()->write($body);
        return $response;
    }
    public function create(Request $request, Response $response): Response{
        $body =  $request->getParsedBody();

        $this->validator = $this->validator->withData($body);

        if(! $this-> validator->validate()){

            $response-> getBody()->write(json_encode($this->validator->errors()));

            return $response->withStatus(422);
        }
        $id = $this->repository->create($body);
        $body = json_encode([
            'message' => 'Product created',
            'id' => $id

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
        $rows = $this->repository->update((int) $id, $body);
        $body = json_encode([
            'message' => 'Product updated',
            'rows' => $rows

        ]);
        $response->getBody()->write($body);
        return $response;
    }
    public function delete(Request $request, Response $response, string $id): Response{
        $rows = $this->repository->delete($id);
        $body = json_encode([
            'message' => 'Product deleted',
            'rows'=> $rows
        ]);
        $response->getBody()->write($body);
        return $response;
    }
}