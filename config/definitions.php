<?php

use App\Database;
use App\Model\UserModel;
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
    }
];