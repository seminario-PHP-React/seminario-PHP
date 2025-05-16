<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Psr7\Factory\ResponseFactory;

use App\Controllers\CardsController;
use App\Controllers\SignupController;
use App\Controllers\LoginController;
use App\Controllers\ProfileController;
use App\Controllers\PartidaController;
use App\Controllers\MazoController;
use App\Controllers\EstadisticasController;
use App\Controllers\JugadaController;
use App\Middleware\RequireAPIKey;
use App\Middleware\AddJsonResponseHeader;
use App\Model\UserModel;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Crear app
$app = AppFactory::create();

// Middleware global para Content-Type JSON
$app->add(new AddJsonResponseHeader());

// Rutas públicas
$app->post('/registro', SignupController::class . ':create');
$app->post('/login', LoginController::class . ':create');
$app->get('/cartas', CardsController::class . ':showByData');
$app->get('/estadisticas', EstadisticasController::class . ':show');

// Rutas protegidas con token JWT
$app->group('', function (RouteCollectorProxy $group) {

    $group->get('/logout', LoginController::class . ':destroy');

    $group->group('/mazos', function (RouteCollectorProxy $group) {
        $group->post('', MazoController::class . ':create');
        $group->delete('/{id:[0-9]+}', MazoController::class . ':delete');
        $group->put('/{id:[0-9]+}', MazoController::class . ':update');
    });

    $group->group('/usuarios', function (RouteCollectorProxy $group) {
        $group->put('/{usuario:[0-9]+}', ProfileController::class . ':update');
        $group->get('/{usuario:[0-9]+}', ProfileController::class . ':showUserData');
        $group->get('/{usuario}/mazos', MazoController::class . ':getUserMazos');
    });

    $group->post('/partidas', PartidaController::class . ':start');
    $group->post('/jugadas', JugadaController::class . ':registrarJugada');
    $group->get('/usuarios/{usuario:[0-9]+}/partidas/{partida:[0-9]+}/cartas', PartidaController::class . ':cartasEnMano');

})->add(new RequireAPIKey($container->get(ResponseFactory::class), $container->get(UserModel::class)));

// Ejecutar app
$app->run();
