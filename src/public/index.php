<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use Slim\Handlers\Strategies\RequestResponseArgs;
use App\Middleware\AddJsonResponseHeader;



require dirname(__DIR__, 2) . '/vendor/autoload.php';

//require '../../vendor/autoload.php';
$builder = new ContainerBuilder;

$container = $builder->addDefinitions(dirname(__DIR__, 2) . '/config/definitions.php')->build();
//$container = $builder->addDefinitions('../../config/definitions.php')->build();


AppFactory::setContainer($container);

$app = AppFactory::create();

$collector = $app->getRouteCollector();
$collector->setDefaultInvocationStrategy(new RequestResponseArgs);
$app->addBodyParsingMiddleware();
$error_middleware = $app->addErrorMiddleware(true, true, true);
$error_handler = $error_middleware->getDefaultErrorHandler();
$error_handler->forceContentType('application/json');
$app->add(new AddJsonResponseHeader);

require '../../config/routes.php';

$app->run();
