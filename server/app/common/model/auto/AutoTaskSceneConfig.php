<?php

namespace app\common\model\auto;

use app\common\model\BaseModel;

/**
 * 自动任务场景开关配置模型
 * Class AutoTaskSceneConfig
 * @package app\common\model\auto
 */
class AutoTaskSceneConfig extends BaseModel
{
    protected $name = 'auto_task_scene_config';

    public function setAllowPlatformsAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if (is_string($value) && $value !== '') {
            return $value;
        }
        return null;
    }

    public function getAllowPlatformsAttr($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || $value === '') {
            return null;
        }
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}
