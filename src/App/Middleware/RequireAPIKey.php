<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Factory\ResponseFactory;

class RequireAPIKey
{
    public function __construct(private ResponseFactory $factory){

    }
    public function __invoke(Request $request, RequestHandler $handler): Response 
    {
        $params = $request->getQueryParams();

        if (! array_key_exists('api-key', $params)){
            $response = $this -> factory->createResponse();
            $response->getBody()->write(json_encode('API key is required'));
            return $response->withStatus(400);
        }
        if ($params['api-key'] !== 'abc'){
            $response = $this -> factory->createResponse();
            $response->getBody()->write(json_encode('invalid API key'));
            return $response->withStatus(401);
        }

        $response = $handler-> handle($request);
        return $response;
    }
    
        
    }
