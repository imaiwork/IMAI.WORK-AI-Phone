<?php

namespace app\adminapi\logic\aiPersona;

use app\common\logic\BaseLogic;
use app\common\service\ConfigService;

/**
 * IP人设设置逻辑
 * Class PersonaMaterialSettingLogic
 * @package app\adminapi\logic\aiPersona
 */
class PersonaMaterialSettingLogic extends BaseLogic
{
    /** 素材清理次数阈值 defaults：与 api 端 MaterialLogic 保持同默认值 */
    const DEFAULT_CLEAN_THRESHOLD = 3;

    /**
     * @notes 获取人设设置
     * @return array
     */
    public static function getConfig()
    {
        return [
            // 一键清理按钮是否在小程序端展示：1显示 0隐藏
            'material_clean_switch' => (int)ConfigService::get('aiPersona', 'material_clean_switch', 0),
            // 素材使用达到该次数后可一键清理
            'material_clean_threshold' => (int)ConfigService::get(
                'aiPersona',
                'material_clean_threshold',
                self::DEFAULT_CLEAN_THRESHOLD
            ),
        ];
    }

    /**
     * @notes 设置人设设置
     * @param $params
     */
    public static function setConfig($params)
    {
        ConfigService::set('aiPersona', 'material_clean_switch', $params['material_clean_switch']);
        ConfigService::set('aiPersona', 'material_clean_threshold', $params['material_clean_threshold']);
    }
}
