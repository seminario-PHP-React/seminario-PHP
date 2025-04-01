<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Factory\ResponseFactory;
use App\Repositories\UserRepository;

class RequireAPIKey
{
    public function __construct(private ResponseFactory $factory, private UserRepository $repository){

    }
    public function __invoke(Request $request, RequestHandler $handler): Response 
    {
        $params = $request->getQueryParams();

        if (! $request->hasHeader('X-API-Key', $params)){
            $response = $this -> factory->createResponse();
            $response->getBody()->write(json_encode('API key is required'));
            return $response->withStatus(400);
        }
        $api_key = $request->getHeaderLine('X-API-Key');
        $user= $this->repository->find('token', $api_key);
        if ($user === false){
            $response = $this -> factory->createResponse();
            $response->getBody()->write(json_encode('invalid API key'));
            return $response->withStatus(401);
        }
        $api_exp_date = strtotime($user['vencimiento_token']);
        if ($api_exp_date < time() ){
            $response = $this -> factory->createResponse();
            $response->getBody()->write(json_encode('Your API key has expired, please login to get a new one.'));
            return $response->withStatus(401);
        }

        $response = $handler-> handle($request);
        return $response;
    }
    
        
    }
