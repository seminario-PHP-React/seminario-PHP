<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Model\JugadaModel;
use App\Model\PartidaModel;
use App\Model\MazoModel;

class JugadaController
{
    public function __construct(
        private JugadaModel  $jugadaModel,
        private PartidaModel $partidaModel,
        private MazoModel $mazoModel
    ) {}

    public function registrarJugada(Request $request, Response $response): Response
{
    $usuario = $request->getAttribute('usuario');
    $datos   = $request->getParsedBody();

    $partidaId       = (int) $datos['partida_id'];
    $cartaIdUsuario  = (int) $datos['carta_id'];
    
    // Obtener el número de jugadas realizadas en la partida
    $numJugada = $this->jugadaModel->cantidadDeRondas($partidaId);

    // Verificar si ya se han jugado 5 rondas
    if ($numJugada >= 5) {
        return $this->responderJSON($response,
            ['error' => 'Esta partida ya se encuentra finalizada'],
            404
        );
    }

    // Validar que la carta esté en la mano del usuario
    $cartasEnMano = $this->partidaModel->getCartasMano($usuario['id'], $partidaId);

    $tieneCarta = array_filter($cartasEnMano, fn($c) => $c['carta_id'] === $cartaIdUsuario);

    if (empty($tieneCarta)) {
        return $this->responderJSON($response,
            ['error' => 'Carta no válida o ya utilizada'],
            403
        );
    }

    // Generar carta del servidor
    $cartaIdServidor = $this->jugadaServidor();

    // Insertar jugada y calcular ganador
    $registroJugada = $this->jugadaModel
                           ->insertarJugada(
                               $partidaId,
                               $cartaIdUsuario,
                               $cartaIdServidor
                           );

    // Determinar el ganador de la ronda
    $resultado = $this->jugadaModel
                      ->determinarGanadorRonda($registroJugada);

    $this->jugadaModel->marcarResultado($resultado, $registroJugada['id']);                  

    
    // Verificar si es la quinta jugada y cerrar la partida
    if (($numJugada + 1) === 5) {  // La jugada actual es la quinta
        $this->partidaModel->actualizarEstadoPartida( 'finalizada', $partidaId);
        $mazoId= $this->partidaModel->encontrarMazoPorPartida($partidaId);
        $this->mazoModel->actualizarEstadoMazo('en_mazo', $mazoId);
    }

    // Determinar quién ganó
    $ganador = match ($resultado) {
        'gano' => 'Usuario',
        'perdio' => 'Servidor',
        default => 'Empate',
    };

    return $this->responderJSON(
        $response,
        ['ganador' => $ganador],
        200
    );
    }


    private function jugadaServidor()
    {
        $num = rand(1, 30);  // O el rango adecuado de tus cartas
        return $num;
    }
    
    private function responderJSON(Response $response, array $payload, int $status): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}    
