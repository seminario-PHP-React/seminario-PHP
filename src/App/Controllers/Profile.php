<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;



class Profile{
    public function __construct(){
        
    }
    public function show(Request $request, Response $response): Response
    {
        $user = $request-> getAttribute('usuario');        
        $api_key= $user['token'];

        $response->getBody()->write("API KEY: $api_key");
        return $response;
    }
    
    
}