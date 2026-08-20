<?php

namespace app\adminapi\validate\geo;

use app\common\validate\BaseValidate;

class GeoPublishValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer',
    ];

    protected $message = [
        'id.require' => '记录ID是必填项',
    ];

    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}
