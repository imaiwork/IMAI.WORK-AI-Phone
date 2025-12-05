<?php
include '../vendor/autoload.php';

use Smf\ConnectionPool\ConnectionPool;
use Smf\ConnectionPool\Connectors\CoroutinePostgreSQLConnector;
use Swoole\Coroutine\PostgreSQL;

go(function () {
    // All PostgreSQL connections: [10, 30]
    $pool = new ConnectionPool(
        [
            'minActive'         => 10,
            'maxActive'         => 30,
            'maxWaitTime'       => 5,
            'maxIdleTime'       => 20,
            'idleCheckInterval' => 10,
        ],
        new CoroutinePostgreSQLConnector,
        [
            'connection_strings' => 'host=127.0.0.1 port=5432 dbname=postgres user=postgres password=xy123456',
        ]
    );
    echo "Initializing connection pool\n";
    $pool->init();
    defer(function () use ($pool) {
        echo "Closing connection pool\n";
        $pool->close();
    });

    echo "Borrowing the connection from pool\n";
    /**@var PostgreSQL $connection */
    $connection = $pool->borrow();

    $result = $connection->query("SELECT * FROM pg_stat_database where datname='postgres';");

    $stat = $connection->fetchAssoc($result);
    echo "Return the connection to pool as soon as possible\n";
    $pool->return($connection);

    var_dump($stat);
});
