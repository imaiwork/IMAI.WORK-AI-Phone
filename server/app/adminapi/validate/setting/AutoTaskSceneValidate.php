<?php

namespace app\adminapi\validate\setting;

use app\common\validate\BaseValidate;

/**
 * 自动任务场景配置验证
 * Class AutoTaskSceneValidate
 * @package app\adminapi\validate\setting
 */
class AutoTaskSceneValidate extends BaseValidate
{
    protected $rule = [
        'items' => 'require|array|validateItems',
    ];

    protected $message = [
        'items.require' => '配置项不能为空',
        'items.array'   => '配置项格式错误',
    ];

    public function sceneSet(): AutoTaskSceneValidate
    {
        return $this->only(['items']);
    }

    /**
     * @notes 校验配置项内容
     * @param mixed $value
     * @return bool|string
     */
    protected function validateItems($value)
    {
        if (!is_array($value) || empty($value)) {
            return '配置项不能为空';
        }

        $scenes = [];
        foreach ($value as $item) {
            if (!is_array($item)) {
                return '配置项格式错误';
            }
            $scene = filter_var($item['scene'] ?? null, FILTER_VALIDATE_INT);
            if (false === $scene || $scene < 1 || $scene > 15) {
                return '任务场景不合法';
            }
            if (in_array($scene, $scenes, true)) {
                return '任务场景不能重复';
            }
            $scenes[] = $scene;

            if (!in_array($item['allow_add'] ?? null, [0, 1, '0', '1'], true)) {
                return '是否允许添加参数错误';
            }

            if (array_key_exists('allow_platforms', $item)) {
                $platformCheck = \app\common\service\auto\AutoTaskSceneConfigService::validateAllowPlatformsInput(
                    $scene,
                    $item['allow_platforms']
                );
                if (true !== $platformCheck) {
                    return $platformCheck;
                }
            }
        }

        return true;
    }
}
