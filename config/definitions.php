<?php

use App\Database;
//use App\Model\UserRepository;
use App\Model\MazoModel;
use App\Controllers\MazoController;


return [
    Database::class => function () {
        return new Database(
            host: '127.0.0.1',
            name: 'seminariophp',
            user: 'root',
            password: ''
        );
    },

    UserRepository::class => function ($container) {
        return new UserRepository($container->get(Database::class));
    },

    MazoModel::class => function ($container) {
        return new MazoModel($container->get(Database::class));
    },

    MazoController::class => function ($container) {
        return new MazoController($container->get(MazoModel::class));
    }
];
