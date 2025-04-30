<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . '/../../../config/config.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Model\UserModel;
use Valitron\Validator;

Validator::langDir(__DIR__ . '/../../../vendor/vlucas/valitron/lang');
Validator::lang('es');

class ProfileController
{
    public function __construct(private UserModel $model, private Validator $validator)
    {
    }


    public function showUserData(Request $request, Response $response, string $usuario): Response
    {
        try {
            $user = $request->getAttribute('usuario');

            if ($usuario != $user['id']) {
                $body = json_encode(['Mensaje' => 'Acceso denegado']);
                $response->getBody()->write($body);
                return $response->withStatus(401);
            }

            $body = json_encode([
                "Nombre" => $user['nombre'],
                "Usuario" => $user['usuario'],
                "Token" => $user['token'],
                "Fecha de vencimiento del token" => date('d-m-Y H:i:s', strtotime($user['vencimiento_token']))
            ]);

            $response->getBody()->write($body);
            return $response;
        } catch (\Exception $e) {
            return $this->handleError($response, $e);
        }
    }

    public function update(Request $request, Response $response, string $usuario): Response
    {
        try {
            $user = $request->getAttribute('usuario');
            $data = $request->getParsedBody() ?? []; // Evita errores si el body es null

            if ($usuario != $user['id']) {
                $body = json_encode(['Mensaje' => 'Acceso denegado']);
                $response->getBody()->write($body);
                return $response->withStatus(401);
            }

            if (empty($data)) {
                $body = json_encode(['Mensaje' => 'Debe enviar un campo válido (nombre o contraseña).']);
                $response->getBody()->write($body);
                return $response->withStatus(400);
            }

            // Configurar reglas dinámicamente según los campos recibidos
            $rules = [];

            if (isset($data['name'])) {
                $rules['name'] = ['required', ['regex', '/^[a-zA-Z\s]+$/'], ['lengthBetween', 6, 20]];
            }

            if (isset($data['password']) || isset($data['password_confirmation'])) {
                $rules['password'] = ['required', ['lengthMin', 8], ['regex', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/']];
                $rules['password_confirmation'] = ['required', ['equals', 'password']];
            }

            // Aplicar solo las reglas necesarias
            $this->validator->mapFieldsRules($rules);
            $this->validator = $this->validator->withData($data);

            if (!$this->validator->validate()) {
                $body = json_encode($this->validator->errors());
                $response->getBody()->write($body);
                return $response->withStatus(400);
            }

            // Si es actualización de contraseña
            if (isset($data['password']) && isset($data['password_confirmation'])) {
                if ($data['password'] !== $data['password_confirmation']) {
                    $body = json_encode(['Mensaje' => 'Las contraseñas no coinciden.']);
                    $response->getBody()->write($body);
                    return $response->withStatus(400);
                }

                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $this->model->update($user['id'], 'password_hash', $hashedPassword);
                $body = json_encode(['Mensaje' => 'Contraseña actualizada con éxito.']);
                $response->getBody()->write($body);
                return $response->withStatus(200);
            }

            // Si es actualización de nombre
            if (isset($data['name'])) {
                $this->model->update($user['id'], 'nombre', $data['name']);
                $body = json_encode(['Mensaje' => 'Nombre actualizado exitosamente.']);
                $response->getBody()->write($body);

                return $response->withStatus(200);
            }

            $body = json_encode(['Mensaje' => 'Debe enviar un campo válido para actualizar.']);
            $response->getBody()->write($body);
            return $response->withStatus(400);
        } catch (\Exception $e) {
            return $this->handleError($response, $e);
        }
    }

    private function handleError(Response $response, \Exception $e): Response
    {
        $errorMessage = [
            'Mensaje' => 'Hubo un error al procesar la solicitud.',
            'Detalle' => $e->getMessage()
        ];
        $response->getBody()->write(json_encode($errorMessage));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
}
