<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use Slim\Handlers\Strategies\RequestResponseArgs;
use App\Middleware\AddJsonResponseHeader;
use App\Controllers\Cards;
use App\Controllers\CardsIndex;
use App\Middleware\GetCard;
use Slim\Routing\RouteCollectorProxy;
use App\Middleware\RequireAPIKey;


require '../../vendor/autoload.php';
$builder = new ContainerBuilder;

$container = $builder->addDefinitions('../../config/definitions.php')->build();

AppFactory::setContainer($container);

$app = AppFactory::create();

$collector = $app->getRouteCollector();
$collector->setDefaultInvocationStrategy(new RequestResponseArgs);
$app->addBodyParsingMiddleware();
$error_middleware = $app->addErrorMiddleware(true, true, true);
$error_handler = $error_middleware->getDefaultErrorHandler();
$error_handler->forceContentType('application/json');
$app->add(new AddJsonResponseHeader);

$app->group('/api', function (RouteCollectorProxy $group){
    $group->get('/card', CardsIndex::class);
    $group->post('/card', [Cards::class, 'create']);

    $group->group('', function (RouteCollectorProxy $group){
        $group->get('/card/{id:[0-9]+}', Cards::class .  ':show');
        $group->patch('/card/{id:[0-9]+}', Cards::class . ':update');
        $group->delete('/card/{id:[0-9]+}', Cards::class . ':delete');

    })->add(GetCard::class);
});
$app->run();
