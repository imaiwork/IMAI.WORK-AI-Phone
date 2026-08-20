<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvDeviceLog extends BaseModel {
    public function setContentAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getContentAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
