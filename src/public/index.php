<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';


use App\Controllers\LoginController;


$app = AppFactory::create();



$app->get('/', function (Request $request, Response $response, $args) {
    require dirname(__DIR__) . '/App/Database.php';
    $database = new App\Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->query("SELECT * FROM carta");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $body=json_encode($data);

    $response->getBody()->write($body);
    return $response-> withHeader('Content-Type', 'application/json');
});

$app->run();

?>