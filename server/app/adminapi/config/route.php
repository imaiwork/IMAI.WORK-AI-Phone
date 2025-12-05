<?php

return [
    'middleware' => [
        // 初始化
        app\adminapi\http\middleware\InitMiddleware::class,
        // 登录验证
        app\adminapi\http\middleware\LoginMiddleware::class,
        // 权限认证
        app\adminapi\http\middleware\AuthMiddleware::class,
        app\adminapi\http\middleware\CheckMiddleware::class,
        app\adminapi\http\middleware\EncryptDataMiddleware::class,
    ],
];
