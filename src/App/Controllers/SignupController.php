<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Model\UserModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Valitron\Validator;
use Firebase\JWT\JWT;

Validator::langDir(__DIR__ . '/../../../vendor/vlucas/valitron/lang');
Validator::lang('es');

class SignupController
{
    public function __construct(private Validator $validator, private UserModel $model)
    {
        $this->validator->mapFieldsRules(
            [
                'name' => ['required'],
                'user_name' => ['required', 'alphaNum', ['lengthBetween', 6, 20]],
                'password' => ['required', ['lengthMin', 8], ['regex', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/']],
                'password_confirmation' => ['required', ['equals', 'password']],
            ]
        );
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $this->validator = $this->validator->withData($data);

            // Verificar si el nombre de usuario ya existe
            if ($this->model->userExists($data['user_name'])) {
                $response->getBody()->write(json_encode(['Mensaje' => 'El nombre de usuario ya existe']));
                return $response->withStatus(400);
            }

            // Validación de datos
            if (!$this->validator->validate()) {
                $response->getBody()->write(json_encode($this->validator->errors()));
                return $response->withStatus(400);
            }

            // Encriptar password
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);

            // Crear usuario
            $user_id = $this->model->create($data); // Método create debería devolver el ID

            // Generar el JWT
            $payload = [
                'sub' => $user_id,              // subject (el ID del usuario)
                'name' => $data['name'],         // el nombre que envió
                'iat' => time(),
                'exp' => time() + 3600
            ];
            $jwt = JWT::encode($payload, $_ENV['JWT_SECRET_KEY'], 'HS256');

            $data['token'] = $jwt;
            $data['token_expiration'] = date('Y-m-d H:i:s', strtotime('+1 hours', time()));

            // Guardar el JWT como token
            $this->model->updateApiKey($user_id, $jwt, date('Y-m-d H:i:s', strtotime('+1 hours')));

            // Responder con éxito
            $response->getBody()->write(json_encode([
                'Mensaje' => 'Usuario creado con éxito',
                'Token' => $jwt
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            // Manejo de errores
            $errorMessage = [
                'Mensaje' => 'Hubo un error al procesar la solicitud.',
                'Detalle' => $e->getMessage()
            ];
            $response->getBody()->write(json_encode($errorMessage));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
