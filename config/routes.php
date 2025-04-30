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

use App\Middleware\RequireAPIKey;
use App\Middleware\GetCard;
use App\Middleware\ActivateSession;
use App\Middleware\RequireLogin;


use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');  // cargar el archivo .env 
$dotenv->load();


$app->group('', function (RouteCollectorProxy $group){
    $group->post('/registro', SignupController::class . ':create' ); //chequeado
    $group->post('/login', LoginController::class . ':create' );//chequeado
    $group->get('/logout', LoginController::class . ':destroy');//chequeado

    $group->group('', function (RouteCollectorProxy $group){
       
        $group->get('/profile/token', ProfileController::class . ':showApiKey');//chequeado

        $group->group('/mazos', function (RouteCollectorProxy $group){
            $group->delete('/{id:[0-9]+}', MazoController::class . ':delete'); //chequeado
            $group->put('/{id:[0-9]+}', MazoController::class . ':update');  //Chequeado
            $group->post('', MazoController::class . ':create'); //chequeado
        });

        $group->group('/usuarios', function (RouteCollectorProxy $group){
            $group->put('/{usuario:[0-9]+}', ProfileController::class . ':update');//chequeado
            $group->get('/{usuario:[0-9]+}', ProfileController::class . ':showUserData');//chequeado
            $group->get('/{usuario}/mazos', MazoController::class . ':getUserMazos'); //chequeado
        });


    })->add(RequireLogin::class);

})->add(ActivateSession::class);


$app->group('', function (RouteCollectorProxy $group){
    $group->post('/partida', [PartidaController::class, 'start']);
    $group->get('/usuarios/{usuario:[0-9]+}/partidas/{partida:[0-9]+}/cartas', [PartidaController::class, 'cartasEnMano']);
    
 
})->add(RequireAPIKey::class);



$app->group('/api', function (RouteCollectorProxy $group){
    $group->post('/card', [CardsController::class, 'create']);
    $group->get('/cartas', CardsController::class . ':showByData');//chequeado


    $group->group('', function (RouteCollectorProxy $group){
        $group->get('/card/{id:[0-9]+}', CardsController::class .  ':show');
        $group->patch('/card/{id:[0-9]+}', CardsController::class . ':update');
        $group->delete('/card/{id:[0-9]+}', CardsController::class . ':delete');    
    })->add(GetCard::class);

 
    
})->add(RequireAPIKey::class);

$app->get('/api/estadisticas', EstadisticasController::class . ':show');
?>