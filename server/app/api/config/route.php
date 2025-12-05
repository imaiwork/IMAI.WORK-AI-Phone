<?php

return [
    'middleware' => [
        app\api\http\middleware\InitMiddleware::class, // 初始化
        app\api\http\middleware\LoginMiddleware::class, // 登录验证
    ],
];
