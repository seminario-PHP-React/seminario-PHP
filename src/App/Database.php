<?php
declare(strict_types=1);
namespace App;

use PDO;

class Database {
    private ?PDO $connection = null;

    public function __construct(
        private string $host,
        private string $name,
        private string $user,
        private string $password
    ) {}

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $dsn = "mysql:host=$this->host;dbname=$this->name;charset=utf8";
            $this->connection = new PDO($dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        }
        return $this->connection;
    }

    public function closeConnection(): void
    {
        $this->connection = null; 
    }
}
