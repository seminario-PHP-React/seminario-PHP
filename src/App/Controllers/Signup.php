<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Valitron\Validator;
use App\Repositories\UserRepository;

class Signup{
    public function __construct(private Validator $validator, private UserRepository $repository){
        $this->validator->mapFieldsRules(
            ['name' => ['required'],
            'user_name' => ['required', 'alphaNum', ['lengthBetween', 6, 20]],
            'password' => ['required',['lengthMin', 8],['regex', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/']],
            'password_confirmation' => ['required', ['equals', 'password']
            ]
        ]);
        
    }
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody(); 
        $this->validator = $this->validator->withData($data);

        if ($this->repository->userExists($data['user_name'])){
            $response->getBody()->write(json_encode(['error' => 'User already exists']));
            return $response->withStatus(400);
        } 

        if (! $this->validator->validate()){
            $response-> getBody()->write(json_encode($this->validator->errors()));

            return $response->withStatus(400);
        }
       

        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $api_key = bin2hex(random_bytes(16));
        $data['api_key'] = $api_key;
        $data['api_key_expiration'] = date('Y-m-d H:i:s', strtotime('+30 days', time()));
        $this->repository->create($data);
        $response-> getBody()->write("Here is your API key: $api_key");
        return $response;
    }
}