<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\UserRepository;
use Valitron\Validator;



class Profile{
    public function __construct(private UserRepository $repository, private Validator $validator){
        
    }
    public function show(Request $request, Response $response): Response
    {
        $user = $request-> getAttribute('user');        
        $api_key= $user['token'];

        $response->getBody()->write("API KEY: $api_key");
        return $response;
    }
    public function update(Request $request, Response $response): Response
    {
    $user = $request->getAttribute('usuario');
    $data = $request->getParsedBody() ?? []; // Evita errores si el body es null

    if (empty($data)) {
        $body= json_encode(['error' => 'Debe enviar un campo válido (user o password).']);
        $response->getBody()->write($body);
        return $response->withStatus(400);
    }

    // Configurar reglas dinámicamente según los campos recibidos
    $rules = [];

    if (isset($data['user'])) {
        $rules['user'] = ['required', 'alphaNum', ['lengthBetween', 6, 20]];
    }

    if (isset($data['password']) || isset($data['password_confirmation'])) {
        $rules['password'] = ['required', ['lengthMin', 8], ['regex', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/']];
        $rules['password_confirmation'] = ['required', ['equals', 'password']];
    }

    // Aplicar solo las reglas necesarias
    $this->validator->mapFieldsRules($rules);
    $this->validator = $this->validator->withData($data);

    if (!$this->validator->validate()) {
        $body= json_encode($this->validator->errors());
        $response->getBody()->write($body);
        return $response->withStatus(400);
    }

    // Si es actualización de contraseña
    if (isset($data['password']) && isset($data['password_confirmation'])) {
        if ($data['password'] !== $data['password_confirmation']) {
            $body = json_encode( 'Las contraseñas no coinciden.');
            $response->getBody()->write($body);
            return $response->withStatus(400);
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $this->repository->update($user['id'], 'password_hash' , $hashedPassword);
        $body = json_encode('Password updated succesfully.');
        $response->getBody()->write($body);
        return $response->withStatus(200);
    }

    // Si es actualización de usuario
    if (isset($data['user'])) {
        if ($this->repository->userExists($data['user'])){
            $response->getBody()->write(json_encode(['error' => 'User already exists']));
            return $response->withStatus(400);
        } 

        $this->repository->update($user['id'], 'usuario', $data['user']);
        $body= json_encode('User updated succesfully.');
        $response->getBody()->write($body);

        return $response->withStatus(200);
    }

    $body= json_encode('You must send a valid field to update.');
    $response->getBody()->write($body);
    return $response->withStatus(400);
}


    
}