<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Model\JugadaModel;
use App\Model\PartidaModel;

class JugadaController {
    public function __construct(private JugadaModel $jModel, private PartidaModel $pModel) {}

    public function registrarJugada(Request $request, Response $response): Response {
        $user = $request->getAttribute('usuario');
        $data = $request->getParsedBody(); 

        $partida= $data['partida_id'];
        $carta = $data['carta_id'];

        $mazoDispP= $this->pModel->getMazoPorPartida($partida, $user['usuario']);
        $mazoDisp= $this->pModel->getCartasMano($partida, $user['usuario']);


        if ($mazoDispP === false) {
            $response->getBody()->write(json_encode('La partida ingresada no se encuentra en curso ')); 
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);  
        }

        foreach ($mazoDisp as $mD) {
            if ($mD['carta_id'] != $carta) {
                continue; // Si la carta no coincide, continuar con la siguiente iteración
            }
            
            $this->jModel-> marco 
            // Si la carta coincide con la buscada, puedes realizar alguna acción aquí
            // Por ejemplo, puedes marcarla como jugada, descartada, etc.
            break; // O terminar el ciclo si ya no necesitas seguir buscando
        }
        
        


    }
}
