<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Model\UserModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Valitron\Validator ;
use Firebase\JWT\JWT;

class SignupController{
    
    public function __construct(private Validator $validator, private UserModel $model){
        
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

        if ($this->model->userExists($data['user_name'])){
            $response->getBody()->write(json_encode(['error' => 'User already exists']));
            return $response->withStatus(400);
        } 

        if (! $this->validator->validate()){
            $response->getBody()->write(json_encode($this->validator->errors()));
            return $response->withStatus(400);
        }
        
        // Encriptar password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Crear usuario primero (y que te devuelva su ID)
        $user_id = $this->model->create($data); // Ojo, que tu método `create` debería devolver el id

        // Ahora sí, generar el JWT
        $payload = [
            'sub' => $user_id,              // subject (el ID del usuario)
            'name' => $data['name'],         // el nombre que envió
            'iat' => time(),
            'exp' => time() + 3600
        ];
        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET_KEY'], 'HS256');

        // Guardar el JWT como api_key
        $this->model->updateApiKey($user_id, $jwt, date('Y-m-d H:i:s', strtotime('+1 hours')));

        // Responder
        $response->getBody()->write(json_encode([
            'message' => 'Usuario creado con éxito',
            'Token' => $jwt
        ]));
       

        
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        
    }
}