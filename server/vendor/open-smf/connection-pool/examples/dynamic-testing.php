<?php
include '../vendor/autoload.php';

use Smf\ConnectionPool\ConnectionPool;
use Smf\ConnectionPool\Connectors\PDOConnector;
use Swoole\Coroutine;

Swoole\Runtime::enableCoroutine();

go(function () {
    // All MySQL connections: [10, 30]
    $pool = new ConnectionPool(
        [
            'minActive'         => 10,
            'maxActive'         => 30,
            'maxWaitTime'       => 5,
            'maxIdleTime'       => 20,
            'idleCheckInterval' => 10,
        ],
        new PDOConnector,
        [
            'dsn'      => 'mysql:host=127.0.0.1;port=3306;dbname=mysql;charset=utf8mb4',
            'username' => 'root',
            'password' => 'xy123456',
            'options'  => [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_TIMEOUT            => 30,
            ],
        ]
    );
    $pool->init();

    // For debug
    $peakCount = 0;
    swoole_timer_tick(1000, function () use ($pool, &$peakCount) {
        $count = $pool->getConnectionCount();
        $idleCount = $pool->getIdleCount();
        if ($peakCount < $count) {
            $peakCount = $count;
        }
        echo "Pool connection count: $count, peak count: $peakCount, idle count: $idleCount\n";
    });

    while (true) {
        $count = mt_rand(1, 45);
        echo "Query count: $count\n";
        for ($i = 0; $i < $count; $i++) {
            go(function () use ($pool) {
                /**@var \PDO $pdo */
                $pdo = $pool->borrow();
                defer(function () use ($pool, $pdo) {
                    $pool->return($pdo);
                });
                $statement = $pdo->query('show status like \'Threads_connected\'');
                $ret = $statement->fetch();
                if (!isset($ret['Variable_name'])) {
                    echo "Invalid query result: \n", print_r($ret, true);
                }
                echo $ret['Variable_name'] . ': ' . $ret['Value'] . "\n";
            });
        }
        Coroutine::sleep(mt_rand(1, 15));
    }
});
