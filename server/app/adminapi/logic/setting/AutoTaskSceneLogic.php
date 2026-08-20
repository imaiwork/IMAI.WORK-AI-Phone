<?php

namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\service\auto\AutoTaskSceneConfigService;

/**
 * 自动任务场景配置逻辑
 * Class AutoTaskSceneLogic
 * @package app\adminapi\logic\setting
 */
class AutoTaskSceneLogic extends BaseLogic
{
    /**
     * @notes 获取自动任务场景配置
     * @return array
     */
    public static function getConfig(): array
    {
        return [
            'items' => AutoTaskSceneConfigService::getItems(),
        ];
    }

    /**
     * @notes 保存自动任务场景配置
     * @param array $params
     * @return bool
     */
    public static function setConfig(array $params): bool
    {
        $items = $params['items'] ?? [];
        if (empty($items)) {
            self::setError('配置项不能为空');
            return false;
        }
        $result = AutoTaskSceneConfigService::saveItems($items);
        if (!$result) {
            self::setError('保存失败');
            return false;
        }
        return true;
    }
}
