<?php
include '../vendor/autoload.php';

use Smf\ConnectionPool\ConnectionPool;
use Smf\ConnectionPool\Connectors\PhpRedisConnector;

// Enable coroutine for PhpRedis
Swoole\Runtime::enableCoroutine();

go(function () {
    // All Redis connections: [10, 30]
    $pool = new ConnectionPool(
        [
            'minActive'         => 10,
            'maxActive'         => 30,
            'maxWaitTime'       => 5,
            'maxIdleTime'       => 20,
            'idleCheckInterval' => 10,
        ],
        new PhpRedisConnector,
        [
            'host'     => '127.0.0.1',
            'port'     => '6379',
            'database' => 0,
            'password' => null,
            'timeout'  => 5,
        ]
    );
    echo "Initializing connection pool\n";
    $pool->init();
    defer(function () use ($pool) {
        echo "Close connection pool\n";
        $pool->close();
    });

    echo "Borrowing the connection from pool\n";
    /**@var Redis $connection */
    $connection = $pool->borrow();

    $connection->set('test', uniqid());
    $test = $connection->get('test');

    echo "Return the connection to pool as soon as possible\n";
    $pool->return($connection);

    var_dump($test);
});
