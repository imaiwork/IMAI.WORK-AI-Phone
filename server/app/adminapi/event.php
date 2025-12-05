<?php

return [
    'listen' => [
        'AppInit' => [],
        'HttpRun' => [],
        'HttpEnd' => ['app\adminapi\listener\OperationLog'],
        'LogLevel' => [],
        'LogWrite' => [],
    ]
];
