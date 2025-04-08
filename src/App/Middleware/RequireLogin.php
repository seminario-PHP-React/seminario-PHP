<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Factory\ResponseFactory;
use App\Model\UserModel;

class RequireLogin
{
    public function __construct(private ResponseFactory $factory, private UserModel $model){

    }
    public function __invoke(Request $request, RequestHandler $handler): Response 
    {
       if (isset($_SESSION['user_id'])){
        $user = $this-> model -> find ('id', $_SESSION['user_id']);
        if ($user){
            $request = $request -> withAttribute('usuario', $user);
            return $handler->handle($request);
    }
    }
    $response = $this->factory-> createResponse();
    $response->getBody()->write('You must be logged in to access this page.');
    return $response->withStatus(401);
    }
}