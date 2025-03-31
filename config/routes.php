<?php 
declare(strict_types=1);
use App\Controllers\Cards;
use App\Controllers\CardsIndex;
use App\Middleware\GetCard;
use Slim\Routing\RouteCollectorProxy;
use App\Middleware\RequireAPIKey;
use App\Controllers\Signup;

$app->post('/signup', Signup::class . ':create' );
$app->group('/api', function (RouteCollectorProxy $group){
    $group->get('/card', CardsIndex::class);
    $group->post('/card', [Cards::class, 'create']);

    $group->group('', function (RouteCollectorProxy $group){
        $group->get('/card/{id:[0-9]+}', Cards::class .  ':show');
        $group->patch('/card/{id:[0-9]+}', Cards::class . ':update');
        $group->delete('/card/{id:[0-9]+}', Cards::class . ':delete');
    })->add(GetCard::class);
})->add(RequireAPIKey::class);

