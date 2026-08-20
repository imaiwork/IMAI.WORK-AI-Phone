<?php

namespace app\api\validate\device;

use app\common\validate\BaseValidate;

/**
 * Device log validate
 */
class LogValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number|gt:0',
        'ids' => 'require|array',
    ];

    protected $message = [
        'id.require' => '请选择日志',
        'id.number' => '日志ID必须为数字',
        'id.gt' => '日志ID必须大于0',
        'ids.require' => '请选择日志',
        'ids.array' => '日志ID必须是数组',
    ];

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneDelete()
    {
        return $this->only(['ids']);
    }
}
