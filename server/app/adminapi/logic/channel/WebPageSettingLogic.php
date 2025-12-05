<?php

namespace app\adminapi\logic\channel;

use app\common\logic\BaseLogic;
use app\common\service\ConfigService;

/**
 * H5设置逻辑层
 * Class HFiveSettingLogic
 * @package app\adminapi\logic\setting\h5
 */
class WebPageSettingLogic extends BaseLogic
{
    /**
     * @notes 获取H5设置
     * @return array
     * @author 段誉
     * @date 2022/3/29 10:34
     */
    public static function getConfig()
    {
        $config = [
            // 渠道状态 0-关闭 1-开启
            'status' => ConfigService::get('web_page', 'status', 1),
            // 关闭后渠道后访问页面 0-空页面 1-自定义链接
            'page_status' => ConfigService::get('web_page', 'page_status', 0),
            // 自定义链接
            'page_url' => ConfigService::get('web_page', 'page_url', ''),
            'url' => request()->domain() . '/mobile'
        ];
        return $config;
    }


    /**
     * @notes H5设置
     * @param $params
     * @author 段誉
     * @date 2022/3/29 10:34
     */
    public static function setConfig($params)
    {
        ConfigService::set('web_page', 'status', $params['status']);
        ConfigService::set('web_page', 'page_status', $params['page_status']);
        ConfigService::set('web_page', 'page_url', $params['page_url']);
    }
}
