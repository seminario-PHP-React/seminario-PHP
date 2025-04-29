<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Factory\ResponseFactory;
use App\Model\UserModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class RequireAPIKey
{
    public function __construct(private ResponseFactory $factory, private UserModel $model)
    {
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if (! $request->hasHeader('Authorization')) {
            $response = $this->factory->createResponse();
            $response->getBody()->write(json_encode(['error' => 'Authorization header required']));
            return $response->withStatus(400);
        }
    
        $authHeader = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);
    
        try {
            // Decodificar el token JWT
            $decoded = JWT::decode($token, new Key('mi_clave_re_secreta_y_segura_123', 'HS256'));
            $userId = $decoded->sub; // El user_id (sub) del payload
    
            // Verificar si el user_id existe en la base de datos
            $user = $this->model->findById($userId); // Suponiendo que tienes un método en tu UserModel llamado findById
            
            if (!$user) {
                $response = $this->factory->createResponse();
                $response->getBody()->write(json_encode(['error' => 'User not found']));
                return $response->withStatus(401); // Usuario no encontrado
            }
    
            // Si el usuario existe, lo agregamos al request
            $request = $request->withAttribute('user_id', $userId);
    
        } catch (\Exception $e) {
            $response = $this->factory->createResponse();
            $response->getBody()->write(json_encode(['error' => 'Invalid or expired token']));
            return $response->withStatus(401);
        }
    
        $response = $handler->handle($request);
        return $response;
    }
}
