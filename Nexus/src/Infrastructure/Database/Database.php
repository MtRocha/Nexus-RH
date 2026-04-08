<?php

declare(strict_types=1);

namespace NexusRH\Infrastructure\Database;

use PDO;
use PDOException;

final class Database
{
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '1433';
        $dbName = getenv('DB_NAME') ?: 'NexusRH';
        $username = getenv('DB_USER') ?: 'sa';
        $password = getenv('DB_PASS') ?: '';

        $dsn = "sqlsrv:Server={$host},{$port};Database={$dbName};TrustServerCertificate=true";

        try {
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new PDOException('Falha na conexao com o banco de dados: ' . $exception->getMessage(), (int) $exception->getCode());
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    private function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new PDOException('Nao e permitido desserializar a conexao singleton.');
    }
}
