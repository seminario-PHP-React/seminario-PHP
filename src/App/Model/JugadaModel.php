<?php

declare(strict_types=1);

namespace App\Model;

use App\Database;
use PDO;

class JugadaModel
{
    public function __construct(private Database $database)
    {
    }

}