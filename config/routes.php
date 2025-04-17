<?php 
declare(strict_types=1);
use App\Controllers\Cards;
use App\Controllers\CardsIndex;
use App\Middleware\GetCard;
use Slim\Routing\RouteCollectorProxy;
use App\Middleware\RequireAPIKey;
use App\Controllers\Signup;
use App\Controllers\Login;
use App\Controllers\Profile;
use App\Middleware\ActivateSession;
use App\Middleware\RequireLogin;
use App\Controllers\Mazo;


$app->group('', function (RouteCollectorProxy $group){
    $group->post('/signup', Signup::class . ':create' );
    $group->post('/login', Login::class . ':create' );
    $group->get('/logout', Login::class . ':destroy');
    $group->get('/profile', Profile::class . ':show')->add(RequireLogin::class);
    $group->get('/usuarios/{usuario}/mazos', Mazo::class . ':getUserMazos')->add(RequireLogin::class); // utilizo path solicitado
    $group->delete('/mazos/{id}', Mazo::class . ':delete')->add(RequireLogin::class); 

})->add(ActivateSession::class);

$app->group('/api', function (RouteCollectorProxy $group){
    $group->get('/card', CardsIndex::class);
    $group->post('/card', [Cards::class, 'create']);

    $group->group('', function (RouteCollectorProxy $group){
        $group->get('/card/{id:[0-9]+}', Cards::class .  ':show');
        $group->patch('/card/{id:[0-9]+}', Cards::class . ':update');
        $group->delete('/card/{id:[0-9]+}', Cards::class . ':delete');
    })->add(GetCard::class);
})->add(RequireAPIKey::class);

?>