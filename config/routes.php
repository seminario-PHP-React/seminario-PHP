<?php
declare(strict_types=1);

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\{
    CardsController,
    SignupController,
    LoginController,
    ProfileController,
    PartidaController,
    MazoController,
    EstadisticasController,
    JugadaController
};
use App\Middleware\RequireAPIKey;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$app->group('', function (RouteCollectorProxy $group) {
    $group->post('/registro', SignupController::class . ':create'); 
    $group->post('/login', LoginController::class . ':create');
    $group->get('/logout', LoginController::class . ':destroy');

    $group->put('/usuarios/{usuario:[0-9]+}', ProfileController::class . ':update')->add(RequireAPIKey::class);
    $group->get('/usuarios/{usuario:[0-9]+}', ProfileController::class . ':showUserData')->add(RequireAPIKey::class);

    $group->post('/partidas', PartidaController::class . ':start')->add(RequireAPIKey::class);
    $group->post('/jugadas', JugadaController::class . ':registrarJugada')->add(RequireAPIKey::class);

    $group->get('/cartas', CardsController::class . ':showByData');

    $group->get('/usuarios/{usuario:[0-9]+}/mazos', MazoController::class . ':getUserMazos')->add(RequireAPIKey::class); 
    $group->delete('/mazos/{id:[0-9]+}', MazoController::class . ':delete')->add(RequireAPIKey::class); 
    $group->put('/mazos/{id:[0-9]+}', MazoController::class . ':update')->add(RequireAPIKey::class);  
    $group->post('/mazos', MazoController::class . ':create')->add(RequireAPIKey::class); 

    $group->get('/usuarios/{usuario:[0-9]+}/partidas/{partida:[0-9]+}/cartas', PartidaController::class . ':cartasEnMano')->add(RequireAPIKey::class);
});

$app->get('/estadisticas', EstadisticasController::class . ':show');
