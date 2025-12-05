<?php
include '../vendor/autoload.php';

use Smf\ConnectionPool\ConnectionPool;
use Smf\ConnectionPool\ConnectionPoolTrait;
use Smf\ConnectionPool\Connectors\CoroutineMySQLConnector;
use Smf\ConnectionPool\Connectors\PhpRedisConnector;
use Swoole\Coroutine\MySQL;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

class HttpServer
{
    use ConnectionPoolTrait;

    protected $swoole;

    public function __construct(string $host, int $port)
    {
        $this->swoole = new Server($host, $port);

        $this->setDefault();
        $this->bindWorkerEvents();
        $this->bindHttpEvent();
    }

    protected function setDefault()
    {
        $this->swoole->set([
            'daemonize'             => false,
            'dispatch_mode'         => 1,
            'max_request'           => 8000,
            'open_tcp_nodelay'      => true,
            'reload_async'          => true,
            'max_wait_time'         => 60,
            'enable_reuse_port'     => true,
            'enable_coroutine'      => true,
            'http_compression'      => false,
            'enable_static_handler' => false,
            'buffer_output_size'    => 4 * 1024 * 1024,
            'worker_num'            => 4, // Each worker holds a connection pool
        ]);
    }

    protected function bindHttpEvent()
    {
        $this->swoole->on('Request', function (Request $request, Response $response) {
            $pool1 = $this->getConnectionPool('mysql');
            /**@var MySQL $mysql */
            $mysql = $pool1->borrow();
            $status = $mysql->query('SHOW STATUS LIKE "Threads_connected"');
            // Return the connection to pool as soon as possible
            $pool1->return($mysql);


            $pool2 = $this->getConnectionPool('redis');
            /**@var \Redis $redis */
            $redis = $pool2->borrow();
            $clients = $redis->info('Clients');
            // Return the connection to pool as soon as possible
            $pool2->return($redis);

            $json = [
                'status'  => $status,
                'clients' => $clients,
            ];
            // Other logic
            // ...
            $response->header('Content-Type', 'application/json');
            $response->end(json_encode($json));
        });
    }

    protected function bindWorkerEvents()
    {
        $createPools = function () {
            // All MySQL connections: [4 workers * 2 = 8, 4 workers * 10 = 40]
            $pool1 = new ConnectionPool(
                [
                    'minActive' => 2,
                    'maxActive' => 10,
                ],
                new CoroutineMySQLConnector,
                [
                    'host'        => '127.0.0.1',
                    'port'        => '3306',
                    'user'        => 'root',
                    'password'    => 'xy123456',
                    'database'    => 'mysql',
                    'timeout'     => 10,
                    'charset'     => 'utf8mb4',
                    'strict_type' => true,
                    'fetch_mode'  => true,
                ]);
            $pool1->init();
            $this->addConnectionPool('mysql', $pool1);

            // All Redis connections: [4 workers * 5 = 20, 4 workers * 20 = 80]
            $pool2 = new ConnectionPool(
                [
                    'minActive' => 5,
                    'maxActive' => 20,
                ],
                new PhpRedisConnector,
                [
                    'host'     => '127.0.0.1',
                    'port'     => '6379',
                    'database' => 0,
                    'password' => null,
                ]);
            $pool2->init();
            $this->addConnectionPool('redis', $pool2);
        };
        $closePools = function () {
            $this->closeConnectionPools();
        };
        $this->swoole->on('WorkerStart', $createPools);
        $this->swoole->on('WorkerStop', $closePools);
        $this->swoole->on('WorkerError', $closePools);
    }

    public function start()
    {
        $this->swoole->start();
    }
}

// Enable coroutine for PhpRedis
Swoole\Runtime::enableCoroutine();
$server = new HttpServer('0.0.0.0', 5200);
$server->start();