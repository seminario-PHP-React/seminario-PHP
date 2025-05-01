<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Model\JugadaModel;
use App\Model\PartidaModel;
use App\Model\MazoModel;
use Exception;

class JugadaController
{
    public function __construct(
        private JugadaModel  $jugadaModel,
        private PartidaModel $partidaModel,
        private MazoModel $mazoModel
    ) {}

    public function registrarJugada(Request $request, Response $response): Response
    {
        try {
            $usuario = $request->getAttribute('usuario');
            $datos   = $request->getParsedBody();

            $partidaId       = (int) $datos['partida_id'];
            $cartaIdUsuario  = (int) $datos['carta_id'];

            // número de jugadas realizadas en la partida?
            $numJugada = $this->jugadaModel->cantidadDeRondas($partidaId);

            // se han jugado 5 rondas?
            if ($numJugada >= 5) {
                return $this->responderJSON($response,
                    ['Mensaje' => 'Esta partida ya se encuentra finalizada'],
                    404
                );
            }

            // está en la mano del usuario?
            $cartasEnMano = $this->partidaModel->getCartasMano($usuario['id'], $partidaId);

            $tieneCarta = array_filter($cartasEnMano, fn($c) => $c['carta_id'] === $cartaIdUsuario);

            if (empty($tieneCarta)) {
                return $this->responderJSON($response,
                    ['Mensaje' => 'Carta no válida o ya utilizada'],
                    403
                );
            }
            $mazoServidor = 1;

            // Generar carta del servidor
            $cartaIdServidor = $this->jugadaServidor($mazoServidor);
            // insertar jugada y calcular ganador
            $registroJugada = $this->jugadaModel->insertarJugada($partidaId, $cartaIdUsuario, $cartaIdServidor, $mazoServidor);

            // quien es el ganador de la ronda?
            $resultado = $this->jugadaModel->determinarGanadorRonda($registroJugada);

            $this->jugadaModel->marcarResultado($resultado['resultado'], $registroJugada['id']);

            // es la quinta jugada? cerrar la partida
            if (($numJugada + 1) === 5) {  // La jugada actual es la quinta
                $this->partidaModel->actualizarEstadoPartida('finalizada', $partidaId);
                $mazoId = $this->partidaModel->encontrarMazoPorPartida($partidaId);
                $this->mazoModel->actualizarEstadoMazo('en_mazo', $mazoId);
                $this->mazoModel->actualizarEstadoMazo('en_mazo', $mazoServidor);
                $rto = $this->determinarResultadoPartida($partidaId);
            }

            // quién ganó?
            $ganador = match ($resultado['resultado']) {
                'gano' => 'Usuario',
                'perdio' => 'Servidor',
                default => 'Empate',
            };

            $payload = [
                'Carta del usuario:' => $cartaIdUsuario,
                'Fuerza del usuario' => $resultado['fuerza_usuario'],
                'Carta del servidor:' => $cartaIdServidor,
                'Fuerza del servidor' => $resultado['fuerza_servidor'],
                'Ganador' => $ganador
            ];

            if (($numJugada + 1) === 5) {
                $this->partidaModel->resultadoUsuario($rto, $partidaId);
                $payload['Mensaje'] = 'Partida finalizada';
                $payload['El usuario'] = $rto;
            }

            return $this->responderJSON(
                $response,
                $payload,
                200
            );
        } catch (Exception $e) {
            // Manejo de error general
            return $this->responderJSON(
                $response,
                ['Mensaje' => 'Error en el proceso de jugada: ' . $e->getMessage()],
                500
            );
        }
    }

    public function determinarResultadoPartida(int $partidaId): string
    {
        try {
            $jugadas = $this->jugadaModel->obtenerJugadasDePartida($partidaId);
            $resultadoUsuario = 0;
            $resultadoServidor = 0;
            $empates = 0;

            foreach ($jugadas as $jugada) {
                // el campo 'el_usuario' es el que indica si ganó, perdió o empató
                if ($jugada['el_usuario'] === 'gano') {
                    $resultadoUsuario++;
                } elseif ($jugada['el_usuario'] === 'perdio') {
                    $resultadoServidor++;
                } elseif ($jugada['el_usuario'] === 'empato') {
                    $empates++;
                }
            }

            if ($resultadoUsuario > $resultadoServidor) {
                return 'gano';
            } elseif ($resultadoServidor > $resultadoUsuario) {
                return 'perdio';
            }

            // Si el número de empates es mayor que las victorias de ambos, consideramos empate
            if ($resultadoUsuario === $resultadoServidor && $empates > 0) {
                return 'empato';
            }

            return 'empato';
        } catch (Exception $e) {
            // Manejo de error general
            throw new Exception('Error al determinar el resultado de la partida: ' . $e->getMessage());
        }
    }

    public function jugadaServidor(int $mazoId): ?int
    {
        try {
            // Obtener todas las cartas disponibles del servidor
            $cartasDisponibles = $this->jugadaModel->obtenerCartasDisponibles($mazoId);

            if (empty($cartasDisponibles)) {
                throw new Exception('No hay cartas disponibles para jugar.');
            }

            // Elegir una carta aleatoria
            $cartaIdServidor = $cartasDisponibles[array_rand($cartasDisponibles)];

            return $cartaIdServidor;

        } catch (Exception $e) {
            // Manejo de errores
            error_log('Error: ' . $e->getMessage()); // Loguea el error para desarrollo
            return null; // ⚠️ Devuelve null, no un 0 inventado
        }
    }

    private function responderJSON(Response $response, array $payload, int $status): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}
