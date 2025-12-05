<?php

namespace app\api\logic;

use app\api\logic\service\SdService;

class SdLogic extends ApiLogic
{
    public static function generate($param)
    {
        $app = new SdService($param, 'generate/sd3');
        $url = $app->generate();
        self::$returnData = ['url' => $url];
        return true;
    }
}