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

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// RUTAS PÚBLICAS 
$app->group('', function (RouteCollectorProxy $group) {
    $group->post('/registro', SignupController::class . ':create'); 
    $group->post('/login', LoginController::class . ':create');
    $group->get('/logout', LoginController::class . ':destroy');
    $group->get('/cartas', CardsController::class . ':showByData');
    $group->get('/estadisticas', EstadisticasController::class . ':show');
});



// RUTAS PRIVADAS 
$app->group('', function (RouteCollectorProxy $group) {

    //  Perfil de usuario
    $group->group('/usuarios', function (RouteCollectorProxy $group) {
        $group->put('/{usuario:[0-9]+}', ProfileController::class . ':update');
        $group->get('/{usuario:[0-9]+}', ProfileController::class . ':showUserData');
        $group->get('/{usuario:[0-9]+}/mazos', MazoController::class . ':getUserMazos');
        $group->get('/{usuario:[0-9]+}/partidas/{partida:[0-9]+}/cartas', PartidaController::class . ':cartasEnMano');
    });

    //  Partidas
    $group->post('/partidas', PartidaController::class . ':start');

    // Jugadas
    $group->post('/jugadas', JugadaController::class . ':registrarJugada');

    // Mazos
    $group->group('/mazos', function (RouteCollectorProxy $group){
        $group->delete('/{id:[0-9]+}', MazoController::class . ':delete'); 
        $group->put('/{id:[0-9]+}', MazoController::class . ':update');  
        $group->post('', MazoController::class . ':create'); 
    });

})->add(RequireAPIKey::class);
