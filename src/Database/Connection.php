<?php
namespace App\Database;

use PDO;

class Connection
{
    private static ?Connection $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $this->pdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public static function getInstance(): Connection
    {
        return self::$instance ??= new self();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
