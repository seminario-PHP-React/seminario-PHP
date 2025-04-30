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
        $group->put('/usuarios/{usuario:[0-9]+}', ProfileController::class . ':update');//chequeado
        $group->get('/usuarios/{usuario:[0-9]+}', ProfileController::class . ':showUserData');//chequeado
        $group->get('/profile/token', ProfileController::class . ':showApiKey');//chequeado
        $group->delete('/mazos/{id:[0-9]+}', MazoController::class . ':delete'); // TODO  validar que id sean numeros
        $group->get('/usuarios/{usuario}/mazos', MazoController::class . ':getUserMazos'); 
    })->add(RequireLogin::class);

})->add(ActivateSession::class);


$app->group('', function (RouteCollectorProxy $group){
    $group->post('/partida', [PartidaController::class, 'start']);
    $group->get('/usuarios/{usuario:[0-9]+}/partidas/{partida:[0-9]+}/cartas', [PartidaController::class, 'cartasEnMano']);

   
   
    $group->post('/mazos', MazoController::class . ':create'); //--chequeando
    $group->put('/mazos/{id}', MazoController::class . ':update'); // TODO  validar que id sean numeros
 
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