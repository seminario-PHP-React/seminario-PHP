<?php 
declare(strict_types=1);
use Slim\Routing\RouteCollectorProxy;

use App\Controllers\CardsController;
use App\Controllers\SignupController;
use App\Controllers\LoginController;
use App\Controllers\ProfileController;
use App\Controllers\MazoController;

use App\Middleware\RequireAPIKey;
use App\Middleware\GetCard;
use App\Middleware\ActivateSession;
use App\Middleware\RequireLogin;


$app->group('', function (RouteCollectorProxy $group){
    $group->post('/signup', SignupController::class . ':create' );
   
    $group->post('/login', LoginController::class . ':create' );
    $group->get('/logout', LoginController::class . ':destroy');
  
    $group->patch('/profile', ProfileController::class . ':update')->add(RequireLogin::class);
    $group->get('/profile/api_key', ProfileController::class . ':showApiKey')->add(RequireLogin::class);
    $group->get('/profile', ProfileController::class . ':showUserData')->add(RequireLogin::class);
    
    $group->get('/usuarios/{usuario}/mazos', MazoController::class . ':getUserMazos')->add(RequireLogin::class); // utilizo path solicitado
    $group->delete('/mazos/{id}', MazoController::class . ':delete')->add(RequireLogin::class); 
    $group->post('/mazos', MazoController::class . ':create')->add(RequireLogin::class); 
    $group->put('/mazos/{id}', MazoController::class . ':update')->add(RequireLogin::class);
 

})->add(ActivateSession::class);

$app->group('/api', function (RouteCollectorProxy $group){
    $group->post('/card', [CardsController::class, 'create']);
    $group->get('/card/{atributo:[A-Za-z]+}/{nombre:[A-Za-z]+}', CardsController::class . ':showByData');


    $group->group('', function (RouteCollectorProxy $group){
        $group->get('/card/{id:[0-9]+}', CardsController::class .  ':show');
        $group->patch('/card/{id:[0-9]+}', CardsController::class . ':update');
        $group->delete('/card/{id:[0-9]+}', CardsController::class . ':delete');    
    })->add(GetCard::class);
})->add(RequireAPIKey::class);

?>