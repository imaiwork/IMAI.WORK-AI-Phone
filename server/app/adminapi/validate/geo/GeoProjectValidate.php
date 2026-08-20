<?php

namespace app\adminapi\validate\geo;

use app\common\validate\BaseValidate;

class GeoProjectValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer',
        'auto_monitor' => 'require|in:0,1',
    ];

    protected $message = [
        'id.require' => '项目ID是必填项',
        'auto_monitor.require' => '自动监测开关是必填项',
        'auto_monitor.in' => '自动监测开关取值非法',
    ];

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneAutoMonitor()
    {
        return $this->only(['id', 'auto_monitor']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}
