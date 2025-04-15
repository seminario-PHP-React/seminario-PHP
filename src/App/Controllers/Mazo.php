<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repositories\MazoRepository;

class Mazo {

    public function __construct(private MazoRepository $repo) {
    }

       
public function getUserMazos(Request $request, Response $response, string $usuario_id): Response {
    $usuario = $request->getAttribute('usuario');

    // Validar que el usuario consultado sea el mismo que está logueado
    if ((int)$usuario_id !== (int)$usuario['id']) {
        $response->getBody()->write(json_encode(["error" => "No autorizado"]));
        return $response->withStatus(401);
    }

    $mazos = $this->repo->getUserMazos($usuario['id']);
    $response->getBody()->write(json_encode($mazos));
    return $response->withHeader("Content-Type", "application/json");
}

}
