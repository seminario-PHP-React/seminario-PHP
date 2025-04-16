<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;

class ActivateSession {
    public function __invoke(Request $request, RequestHandler $handler): Response 
    {
        if (session_status() !== PHP_SESSION_ACTIVE){
            session_start();
        }
        
        // si el usuario esta logueado, lo agrego como atributo al request para dsp hacer getAtributte
        if (isset($_SESSION['user_id'])) {
            $usuario = [
                'id' => $_SESSION['user_id']
            ];
            $request = $request->withAttribute('usuario', $usuario);
        }

        $response = $handler->handle($request);
        return $response;
    }
}
     

