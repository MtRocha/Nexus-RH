<?php

declare(strict_types=1);

namespace NexusRH\DAO;

use NexusRH\Infrastructure\Database\Database;
use PDO;

abstract class BaseDAO
{
    protected PDO $connection;

    public function __construct()
    {
        $this->connection = Database::getInstance()->getConnection();
    }
}
