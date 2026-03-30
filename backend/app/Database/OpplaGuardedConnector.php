<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;
use PDO;

class OpplaGuardedConnector extends PostgresConnector
{
    /**
     * Establish a database connection.
     *
     * @param  array  $config
     * @return \PDO
     */
    public function connect(array $config)
    {
        // Get the PDO connection using the parent PostgresConnector
        $connection = parent::connect($config);

        return $connection;
    }
}
