<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Model\UserModel;
use Exception;

class LoginController {
    public function __construct(private UserModel $model) {}

    public function create(Request $request, Response $response): Response
    {
        // Headers CORS para permitir peticiones desde React
        $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        $response = $response->withHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
        $response = $response->withHeader('Access-Control-Allow-Headers', 'Content-Type');
        
        // Manejar preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            return $response->withStatus(200);
        }
        
        try {
            $data = $request->getParsedBody(); 

            if (!isset($data['usuario'], $data['contraseña'])) {
                $response->getBody()->write(json_encode(['Mensaje' => 'Faltan campos en el cuerpo de la solicitud']));
                return $response->withStatus(400);
            }    

            $user = $this->model->find('usuario', $data['usuario']);

            if (!$user || $user['usuario'] !== $data['usuario'] ||  !password_verify($data['contraseña'], $user['password'])) {
                $response->getBody()->write(json_encode(['Mensaje' => 'El usuario o la contraseña son incorrectos']));
                return $response->withStatus(401);
            }
            

            $payload = [
                'sub' => (int)$user['id'],
                'name' => $user['nombre'],
                'iat' => time(),
                'exp' => time() + 3600
            ];

            $jwt = JWT::encode($payload, $_ENV['JWT_SECRET_KEY'], 'HS256');
            $now = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $this->model->update((int)$user['id'], 'token', $jwt);
            $this->model->update((int)$user['id'], 'vencimiento_token', $now);

            $response->getBody()->write(json_encode([
                'Mensaje' => 'Bienvenido ' . $user['nombre'],
                'Token' => $jwt,
                'id' => (int)$user['id']
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Ocurrió un error al iniciar sesión',
                'detalle' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function destroy(Request $request, Response $response): Response
    {
        try {
            session_destroy();
            $response->getBody()->write(json_encode(['Mensaje' => 'Sesión cerrada con éxito']));
            return $response->withStatus(302);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Ocurrió un error al cerrar la sesión',
                'detalle' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
