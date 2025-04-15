<?php

use App\Database;
use App\Repositories\UserRepository;
use App\Repositories\MazoRepository;
use App\Controllers\Mazo;

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

    MazoRepository::class => function ($container) {
        return new MazoRepository($container->get(Database::class));
    },

    Mazo::class => function ($container) {
        return new Mazo($container->get(MazoRepository::class));
    }
];
