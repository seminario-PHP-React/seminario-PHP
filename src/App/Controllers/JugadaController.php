<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Model\JugadaModel;
use App\Model\PartidaModel;
use App\Model\MazoModel;
use App\Model\UserModel;
use Exception;

class JugadaController
{
    public function __construct(
        private JugadaModel  $jugadaModel,
        private PartidaModel $partidaModel,
        private MazoModel $mazoModel,
        private UserModel $userModel
    ) {}

    public function registrarJugada(Request $request, Response $response): Response
    {
        try {
            $usuarioId = $request->getAttribute('usuario_id');
            if (!$usuarioId) {
                $payload = ['Mensaje' => 'Usuario no autenticado'];
                $response->getBody()->write(json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            $usuario = $this->userModel->findById((int)$usuarioId);
            if (!$usuario) {
                $payload = ['Mensaje' => 'Usuario no encontrado'];
                $response->getBody()->write(json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            $datos = $request->getParsedBody();

            $partidaId       = (int) ($datos['partida_id'] ?? 0);
            $cartaIdUsuario  = (int) ($datos['carta_id'] ?? 0);

            if (!$partidaId || !$cartaIdUsuario) {
                $payload = ['Mensaje' => 'Faltan datos obligatorios: partida_id o carta_id'];
                $response->getBody()->write(json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $numJugada = $this->jugadaModel->cantidadDeRondas($partidaId);

            if ($numJugada >= 5) {
                $payload = ['Mensaje' => 'Esta partida ya se encuentra finalizada'];
                $response->getBody()->write(json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $cartasEnMano = $this->partidaModel->getCartasMano($usuarioId, $partidaId);

            $tieneCarta = array_filter($cartasEnMano, fn($c) => (int)$c['carta_id'] === $cartaIdUsuario);

            if (empty($tieneCarta)) {
                $payload = ['Mensaje' => 'Carta no válida o ya utilizada'];
                $response->getBody()->write(json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            $mazoServidor = 1;

            $cartaIdServidor = $this->jugadaServidor($mazoServidor);
            if ($cartaIdServidor === null) {
                $payload = ['Mensaje' => 'Error al obtener carta del servidor'];
                $response->getBody()->write(json_encode($payload));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }

            $registroJugada = $this->jugadaModel->insertarJugada($partidaId, $cartaIdUsuario, $cartaIdServidor, $mazoServidor);

            $resultado = $this->jugadaModel->determinarGanadorRonda($registroJugada);

            $this->jugadaModel->marcarResultado($resultado['resultado'], $registroJugada['id']);

            if (($numJugada + 1) === 5) {
                $this->partidaModel->actualizarEstadoPartida('finalizada', $partidaId);
                $mazoId = $this->partidaModel->encontrarMazoPorPartida($partidaId);
                $this->mazoModel->actualizarEstadoMazo('en_mazo', $mazoId);
                $this->mazoModel->actualizarEstadoMazo('en_mazo', $mazoServidor);
                $rto = $this->determinarResultadoPartida($partidaId);
            }

            $ganador = match ($resultado['resultado']) {
                'gano' => 'Usuario',
                'perdio' => 'Servidor',
                default => 'Empate',
            };

            $payload = [
                'Carta del usuario' => $cartaIdUsuario,
                'Fuerza del usuario' => $resultado['fuerza_usuario'],
                'Carta del servidor' => $cartaIdServidor,
                'Fuerza del servidor' => $resultado['fuerza_servidor'],
                'Ganador' => $ganador
            ];

            if (($numJugada + 1) === 5) {
                $this->partidaModel->resultadoUsuario($rto, $partidaId);
                $payload['Mensaje'] = 'Partida finalizada';
                $payload['Resultado'] = $rto;
            }

            $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $payload = ['Mensaje' => 'Error en el proceso de jugada: ' . $e->getMessage()];
            $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
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

            if ($resultadoUsuario === $resultadoServidor && $empates > 0) {
                return 'empato';
            }

            return 'empato';
        } catch (Exception $e) {
            throw new Exception('Error al determinar el resultado de la partida: ' . $e->getMessage());
        }
    }

    public function jugadaServidor(int $mazoId): ?int
    {
        try {
            $cartasDisponibles = $this->jugadaModel->obtenerCartasDisponibles($mazoId);

            if (empty($cartasDisponibles)) {
                throw new Exception('No hay cartas disponibles para jugar.');
            }

            $cartaIdServidor = $cartasDisponibles[array_rand($cartasDisponibles)];

            return $cartaIdServidor;
        } catch (Exception $e) {
            error_log('Error: ' . $e->getMessage());
            return null;
        }
    }
}
