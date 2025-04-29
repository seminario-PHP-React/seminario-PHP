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
        $response->getBody()->write('Fields are missing');
        return $response->withStatus(400);
    }    

    $user = $this->model->find('usuario', $data['user']);
    
    if (! $user) {
        $response->getBody()->write('User not found');
        return $response->withStatus(404);
    }

    if (trim(strtolower($user['nombre'])) !== trim(strtolower($data['name'])) || 
        !password_verify($data['password'], $user['password'])) {
        $response->getBody()->write('Unauthorized');
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
    $jwt = JWT::encode($payload, 'mi_clave_re_secreta_y_segura_123', 'HS256');

    if ($token_exp < time()) {
        $new_api_key = $jwt;
        $now = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->model->update($user['id'], 'token', $new_api_key);
        $this->model->update($user['id'], 'vencimiento_token', $now);
    }

    $response->getBody()->write(json_encode([
        'Bienvenido' => $user['nombre'],
        'Tu token es' => $jwt
    ]));
    return $response->withStatus(200);
}

    
    public function destroy(Request $request, Response $response): Response{
        session_destroy();
        $response->getBody()->write('Logged out');
        return $response->withStatus(302);
    }
}
