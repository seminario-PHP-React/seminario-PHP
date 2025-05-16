<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Model\UserModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
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
            $usuarioIdToken = $request->getAttribute('usuario_id');
            if ($usuario !== (string)$usuarioIdToken) {
                $body = json_encode(['Mensaje' => 'Acceso denegado']);
                $response->getBody()->write($body);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            $user = $this->model->findById((int)$usuarioIdToken);
            if (!$user) {
                $body = json_encode(['Mensaje' => 'Usuario no encontrado']);
                $response->getBody()->write($body);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $body = json_encode([
                "Nombre" => $user['nombre'],
                "Usuario" => $user['usuario'],
                // No conviene devolver token ni fecha en perfil
            ]);

            $response->getBody()->write($body);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            return $this->handleError($response, $e);
        }
    }

    public function update(Request $request, Response $response, string $usuario): Response
    {
        try {
            $usuarioIdToken = $request->getAttribute('usuario');
            var_dump($usuarioIdToken['id']);
            var_dump($usuario);
            if ((int) $usuario !== $usuarioIdToken['id']) {
                $body = json_encode(['Mensaje' => 'Acceso denegado']);
                $response->getBody()->write($body);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            $data = $request->getParsedBody() ?? [];

            if (empty($data)) {
                $body = json_encode(['Mensaje' => 'Debe enviar un campo válido (nombre o contraseña).']);
                $response->getBody()->write($body);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $rules = [];

            if (isset($data['nombre'])) {
                $rules['nombre'] = ['required', ['regex', '/^[a-zA-Z\s]+$/']];
            }

            if (isset($data['contraseña'])) {
                $rules['contraseña'] = [
                    'required',
                    ['lengthMin', 8],
                    ['regex', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/']
                ];
            }

            if (!empty($rules)) {
                $this->validator->mapFieldsRules($rules);
                $this->validator = $this->validator->withData($data);

                if (!$this->validator->validate()) {
                    $body = json_encode([
                        'Mensaje' => 'Error de validación',
                        'Errores' => $this->validator->errors()
                    ]);
                    $response->getBody()->write($body);
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            if (isset($data['contraseña'])) {
                $hashedPassword = password_hash($data['contraseña'], PASSWORD_DEFAULT);
                $this->model->update((int)$usuarioIdToken, 'password', $hashedPassword);
                $body = json_encode(['Mensaje' => 'Contraseña actualizada con éxito.']);
                $response->getBody()->write($body);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }

            if (isset($data['nombre'])) {
                $this->model->update((int)$usuarioIdToken, 'nombre', $data['nombre']);
                $body = json_encode(['Mensaje' => 'Nombre actualizado exitosamente.']);
                $response->getBody()->write($body);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }

            $body = json_encode(['Mensaje' => 'Debe enviar un campo válido para actualizar.']);
            $response->getBody()->write($body);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
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
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
}
