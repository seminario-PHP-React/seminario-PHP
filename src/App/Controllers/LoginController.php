<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Model\UserModel;

class LoginController{
    public function __construct(private UserModel $model)
    {
        
    }
    public function create(Request $request, Response $response): Response
    {
           
        $data = $request->getParsedBody(); 
        $user = $this->model->find('usuario', $data['user']);
        if (!isset($data['user'], $data['name'], $data['password'])) {
            $response->getBody()->write('Fields are missing');
            return $response->withStatus(400);
        }     
        
        if ($user && $user['nombre'] === $data['name']  && password_verify($data['password'], $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id']; 
            
            // Obtener la fecha de expiración del usuario
            $token_exp = strtotime($user['vencimiento_token']); // Convertir a timestamp

            if ($token_exp < time()) {
                $new_api_key = bin2hex(random_bytes(16));
                $now = date('Y-m-d H:i:s', strtotime('+1 hour'));  

                // Actualizar el token y la fecha de expiración en la base de datos
                $this->model->update($user['id'], 'token', $new_api_key);
                $this->model->update($user['id'], 'vencimiento_token', $now);
            }

            $response->getBody()->write('Logged in');
            return $response->withStatus(200);
        }

        $response->getBody()->write('Unauthorized');
        return $response->withStatus(401);
    }
    
    public function destroy(Request $request, Response $response): Response{
        session_destroy();
        $response->getBody()->write('Logged out');
        return $response->withStatus(302);
    }
}
