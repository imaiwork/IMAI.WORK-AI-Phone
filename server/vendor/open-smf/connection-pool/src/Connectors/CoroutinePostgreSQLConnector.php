<?php

namespace Smf\ConnectionPool\Connectors;

use Swoole\Coroutine\PostgreSQL;

class CoroutinePostgreSQLConnector implements ConnectorInterface
{
    public function connect(array $config)
    {
        if (!isset($config['connection_strings'])) {
            throw new \InvalidArgumentException('The key "connection_string" is missing.');
        }
        $connection = new PostgreSQL();
        $ret = $connection->connect($config['connection_strings']);
        if ($ret === false) {
            throw new \RuntimeException(sprintf('Failed to connect PostgreSQL server: %s', $connection->error));
        }
        return $connection;
    }

    public function disconnect($connection)
    {
        /**@var PostgreSQL $connection */
    }

    public function isConnected($connection): bool
    {
        /**@var PostgreSQL $connection */
        return true;
    }

    public function reset($connection, array $config)
    {
        /**@var PostgreSQL $connection */
    }

    public function validate($connection): bool
    {
        return $connection instanceof PostgreSQL;
    }
}