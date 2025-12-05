<?php

namespace Smf\ConnectionPool;

trait ConnectionPoolTrait
{
    /**
     * @var ConnectionPool[] $pools
     */
    protected $pools = [];

    /**
     * Add a connection pool
     * @param string $key
     * @param ConnectionPool $pool
     */
    public function addConnectionPool(string $key, ConnectionPool $pool)
    {
        $this->pools[$key] = $pool;
    }

    /**
     * Get a connection pool by key
     * @param string $key
     * @return ConnectionPool
     */
    public function getConnectionPool(string $key): ConnectionPool
    {
        return $this->pools[$key];
    }

    /**
     * Close the connection by key
     * @param string $key
     * @return bool
     */
    public function closeConnectionPool(string $key)
    {
        return $this->pools[$key]->close();
    }

    /**
     * Close all connection pools
     */
    public function closeConnectionPools()
    {
        foreach ($this->pools as $pool) {
            $pool->close();
        }
    }
}