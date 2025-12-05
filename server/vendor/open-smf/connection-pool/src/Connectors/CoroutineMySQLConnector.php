<?php

namespace Smf\ConnectionPool\Connectors;

use Swoole\Coroutine\MySQL;

class CoroutineMySQLConnector implements ConnectorInterface
{
    public function connect(array $config)
    {
        $connection = new MySQL();
        if ($connection->connect($config) === false) {
            throw new \RuntimeException(sprintf('Failed to connect MySQL server: [%d] %s', $connection->connect_errno, $connection->connect_error));
        }
        return $connection;
    }

    public function disconnect($connection)
    {
        /**@var MySQL $connection */
        $connection->close();
    }

    public function isConnected($connection): bool
    {
        /**@var MySQL $connection */
        return $connection->connected;
    }

    public function reset($connection, array $config)
    {

    }

    public function validate($connection): bool
    {
        return $connection instanceof MySQL;
    }
}