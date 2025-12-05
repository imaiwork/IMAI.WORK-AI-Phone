<?php

namespace Smf\ConnectionPool\Connectors;

interface ConnectorInterface
{
    /**
     * Connect to the specified Server and returns the connection resource
     * @param array $config
     * @return mixed
     */
    public function connect(array $config);

    /**
     * Disconnect and free resources
     * @param mixed $connection
     * @return mixed
     */
    public function disconnect($connection);

    /**
     * Whether the connection is established
     * @param mixed $connection
     * @return bool
     */
    public function isConnected($connection): bool;

    /**
     * Reset the connection
     * @param mixed $connection
     * @param array $config
     * @return mixed
     */
    public function reset($connection, array $config);

    /**
     * Validate the connection
     *
     * @param mixed $connection
     * @return bool
     */
    public function validate($connection): bool;
}