<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Model\UserModel;

class LoginController{
    public function __construct(private UserModel $model)
    {
        
    }
    public function create(Request $request, Response $response): Response
    {
    $data = $request->getParsedBody(); 

    if (!isset($data['user'], $data['name'], $data['password'])) {
        $response->getBody()->write(json_encode(['Mensaje' => 'Faltan campos en el cuerpo de la solicitud']));
        return $response->withStatus(401);
    }    

    $user = $this->model->find('usuario', $data['user']);
    
    if ( $user['usuario'] !== $data['user'] || $user['nombre'] !== $data['name'] || !password_verify($data['password'], $user['password'])) {
        $response->getBody()->write(json_encode(['error' => 'El usuario, el nombre o la contraseña son incorrectos']));
        return $response->withStatus(401);
    }

    $_SESSION['user_id'] = $user['id']; 

    $token_exp = strtotime($user['vencimiento_token']);
    $payload = [
        'sub' => $user['id'],
        'name' => $user['nombre'],
        'iat' => time(),
        'exp' => time() + 3600
    ];
    $jwt = JWT::encode($payload, $_ENV['JWT_SECRET_KEY'], 'HS256');

    if ($token_exp < time()) {
        $new_token = $jwt;
        $now = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->model->update($user['id'], 'token', $new_token);
        $this->model->update($user['id'], 'vencimiento_token', $now);
    }
    $token= $this->model->getAPIKey($user['id']);
    $response->getBody()->write(json_encode([
        'Bienvenido' => $user['nombre'],
        'Tu token es' =>$token
    ]));
    return $response->withStatus(200);
}

    
    public function destroy(Request $request, Response $response): Response{
        session_destroy();
        $response->getBody()->write(json_encode(['Mensaje'=>'Sesión cerrada con éxito']));
        return $response->withStatus(302);
    }
}
