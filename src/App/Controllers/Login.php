<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\UserRepository;

class Login {
    public function __construct(private UserRepository $repository) {}

    public function create(Request $request, Response $response): Response
{
    $data = $request->getParsedBody(); 
    $user = $this->repository->find('usuario', $data['user']);
    
    if ($user && password_verify($data['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $response->getBody()->write('Logged in');
        return $response->withStatus(200);
    }

    $response->getBody()->write('Unauthorized');
    return $response->withStatus(401);
}


    public function destroy(Request $request, Response $response): Response
    {
        session_destroy();
        $response->getBody()->write('Logged out');
        return $response->withStatus(302);
    }
}
