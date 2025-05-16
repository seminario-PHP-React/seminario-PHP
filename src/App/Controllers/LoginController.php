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
    try {
        $data = $request->getParsedBody();

        if (!isset($data['usuario'], $data['password'])) {
            $response->getBody()->write(json_encode(['Mensaje' => 'Faltan campos usuario o password']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $user = $this->model->find('usuario', $data['usuario']);

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            $response->getBody()->write(json_encode(['Mensaje' => 'Usuario o contraseña incorrectos']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $payload = [
            'sub' => (int)$user['id'],
            'name' => $user['nombre'],
            'iat' => time(),
            'exp' => time() + 3600 // 1 hora de duración
        ];

        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET_KEY'], 'HS256');

        $response->getBody()->write(json_encode([
            'Bienvenido' => $user['nombre'],
            'token' => $jwt
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'error' => 'Error al iniciar sesión',
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
