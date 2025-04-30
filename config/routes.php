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


// En tu archivo principal (por ejemplo, index.php o bootstrap.php)
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');  // Cargar el archivo .env desde el directorio raíz
$dotenv->load();


$app->group('', function (RouteCollectorProxy $group){
    $group->post('/signup', SignupController::class . ':create' );
    $group->post('/login', LoginController::class . ':create' );
    $group->get('/logout', LoginController::class . ':destroy');
    $group->patch('/profile', ProfileController::class . ':update')->add(RequireLogin::class);
    $group->get('/profile/token', ProfileController::class . ':showApiKey')->add(RequireLogin::class);
    $group->get('/usuarios/{usuario:[0-9]+}', ProfileController::class . ':showUserData')->add(RequireLogin::class);

})->add(ActivateSession::class);


$app->group('', function (RouteCollectorProxy $group){
    $group->post('/partida', [PartidaController::class, 'start']);
    $group->get('/usuarios/{usuario:[0-9]+}/partidas/{partida:[0-9]+}/cartas', [PartidaController::class, 'cartasEnMano']);
    $group->get('/usuarios/{usuario}/mazos', MazoController::class . ':getUserMazos'); // TODO  validar que usuario sean palabras
    $group->delete('/mazos/{id}', MazoController::class . ':delete'); // TODO  validar que id sean numeros
    $group->post('/mazos', MazoController::class . ':create');
    $group->put('/mazos/{id}', MazoController::class . ':update'); // TODO  validar que id sean numeros
 
})->add(RequireAPIKey::class);



$app->group('/api', function (RouteCollectorProxy $group){
    $group->post('/card', [CardsController::class, 'create']);
    $group->get('/card/{atributo:[A-Za-z]+}/{nombre:[A-Za-z]+}', CardsController::class . ':showByData');


    $group->group('', function (RouteCollectorProxy $group){
        $group->get('/card/{id:[0-9]+}', CardsController::class .  ':show');
        $group->patch('/card/{id:[0-9]+}', CardsController::class . ':update');
        $group->delete('/card/{id:[0-9]+}', CardsController::class . ':delete');    
    })->add(GetCard::class);

 
    
})->add(RequireAPIKey::class);

$app->get('/api/estadisticas', EstadisticasController::class . ':show');
?>