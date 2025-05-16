<?php 
declare(strict_types=1);
use Slim\Routing\RouteCollectorProxy;

use App\Controllers\CardsController;
use App\Controllers\SignupController;
use App\Controllers\LoginController;
use App\Controllers\ProfileController;
use App\Controllers\PartidaController;
use App\Controllers\MazoController;
use App\Controllers\EstadisticasController;
use App\Controllers\JugadaController;
use App\Middleware\RequireAPIKey;
use App\Middleware\ActivateSession;
use App\Middleware\RequireLogin;


use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');  // cargar el archivo .env 
$dotenv->load();


$app->group('', function (RouteCollectorProxy $group){
    $group->post('/registro', SignupController::class . ':create' ); 
    $group->post('/login', LoginController::class . ':create' );
    $group->get('/logout', LoginController::class . ':destroy');
    $group->put('/usuarios/{usuario:[0-9]+}', ProfileController::class . ':update')->add(RequireAPIKey::class);
    $group->get('/usuarios/{usuario:[0-9]+}', ProfileController::class . ':showUserData')->add(RequireAPIKey::class);
    $group->group('', function (RouteCollectorProxy $group){
        $group->group('/mazos', function (RouteCollectorProxy $group){
            $group->delete('/{id:[0-9]+}', MazoController::class . ':delete'); 
            $group->put('/{id:[0-9]+}', MazoController::class . ':update');  
            $group->post('', MazoController::class . ':create'); 
        });

        $group->group('/usuarios', function (RouteCollectorProxy $group){

            
            $group->get('/{usuario}/mazos', MazoController::class . ':getUserMazos'); 
        });
        $group->post('/jugadas',JugadaController::class . ':registrarJugada');
    })->add(RequireLogin::class);
})->add(ActivateSession::class);


$app->group('', function (RouteCollectorProxy $group){
    $group->post('/partida', PartidaController::class . ':start');
    $group->get('/usuarios/{usuario:[0-9]+}/partidas/{partida:[0-9]+}/cartas', PartidaController::class . ':cartasEnMano');
    $group->get('/cartas', CardsController::class . ':showByData');
})->add(RequireAPIKey::class);


$app->get('/estadisticas', EstadisticasController::class . ':show');



?>