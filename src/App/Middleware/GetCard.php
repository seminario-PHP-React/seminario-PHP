<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Model\CardModel;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpNotFoundException;

class GetCard
{
    public function __construct(private CardModel $model){

    }
    
        
    
    public function __invoke(Request $request, RequestHandler $handler): Response 
    {
        $context = RouteContext::fromRequest($request);
        $route = $context->getRoute();
        $id = $route-> getArgument('id');
        $Card = $this->model->getById((int)$id);
        if($Card === false){
           throw new HttpNotFoundException($request, message: 'Card not found' );
        }
        $request = $request-> withAttribute('Card', $Card);
        return $handler-> handle($request);
    }
    
}     