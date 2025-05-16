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
                'password' => ['required', ['lengthMin', 8], ['regex', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/']]
            ]
        );
    }

    // ...

public function create(Request $request, Response $response): Response
{
    try {
        $data = $request->getParsedBody();
        $this->validator = $this->validator->withData($data);

        if ($this->model->userExists($data['user_name'])) {
            $response->getBody()->write(json_encode(['Mensaje' => 'El nombre de usuario ya existe']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (!$this->validator->validate()) {
            $response->getBody()->write(json_encode($this->validator->errors()));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $user_id = $this->model->create($data);

        $response->getBody()->write(json_encode([
            'Mensaje' => 'Usuario creado con éxito',
            'Usuario ID' => $user_id
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

    } catch (\Exception $e) {
        $errorMessage = [
            'Mensaje' => 'Hubo un error al procesar la solicitud.',
            'Detalle' => $e->getMessage()
        ];
        $response->getBody()->write(json_encode($errorMessage));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
}

}
