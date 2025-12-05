<?php

namespace app\adminapi\logic\channel;

use app\common\logic\BaseLogic;
use app\common\service\ConfigService;

/**
 * 微信开放平台
 * Class AppSettingLogic
 * @package app\adminapi\logic\setting\app
 */
class OpenSettingLogic extends BaseLogic
{

    /**
     * @notes 获取微信开放平台设置
     * @return array
     * @author 段誉
     * @date 2022/3/29 11:03
     */
    public static function getConfig()
    {
        $config = [
            'app_id' => ConfigService::get('open_platform', 'app_id', ''),
            'app_secret' => ConfigService::get('open_platform', 'app_secret', ''),
        ];

        return $config;
    }


    /**
     * @notes 微信开放平台设置
     * @param $params
     * @author 段誉
     * @date 2022/3/29 11:03
     */
    public static function setConfig($params)
    {
        ConfigService::set('open_platform', 'app_id', $params['app_id'] ?? '');
        ConfigService::set('open_platform', 'app_secret', $params['app_secret'] ?? '');
    }
}
