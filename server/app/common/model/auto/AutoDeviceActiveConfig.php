<?php

namespace app\common\model\auto;

use app\common\model\BaseModel;

class AutoDeviceActiveConfig extends BaseModel {
    public function setExecTimeAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getExecTimeAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}