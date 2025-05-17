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
                'nombre' => ['required'],
                'usuario' => [
                    'required',
                    'alphaNum',
                    ['lengthBetween', 6, 20],
                    ['regex', '/^[a-zA-Z0-9]+$/']
                ],
                'contraseña' => [
                    'required',
                    ['lengthMin', 8],
                    ['regex', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/']
                ]
            ]
        );

        // Agregar mensajes personalizados
        $this->validator->labels([
            'nombre' => 'Nombre',
            'usuario' => 'Usuario',
            'contraseña' => 'Contraseña'
        ]);

        // Mensaje personalizado para la validación de contraseña
        $this->validator->message('La contraseña debe tener al menos 8 caracteres, incluyendo mayúsculas, minúsculas, números y caracteres especiales');
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Verificar campos requeridos
            if (!isset($data['nombre'], $data['usuario'], $data['contraseña'])) {
                $response->getBody()->write(json_encode(['Mensaje' => 'Faltan campos requeridos']));
                return $response->withStatus(400);
            }

            $this->validator = $this->validator->withData($data);

            // Verificar si el nombre de usuario ya existe
            if ($this->model->userExists($data['usuario'])) {
                $response->getBody()->write(json_encode(['Mensaje' => 'El nombre de usuario ya existe']));
                return $response->withStatus(400);
            }

            // Validación de datos
            if (!$this->validator->validate()) {
                $response->getBody()->write(json_encode([
                    'Mensaje' => 'Error de validación',
                    'Errores' => $this->validator->errors()
                ]));
                return $response->withStatus(400);
            }

            // Encriptar password
            $data['password_hash'] = password_hash($data['contraseña'], PASSWORD_DEFAULT);

            // Crear usuario
            $user_id = $this->model->create($data);

            // Responder con éxito
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
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
