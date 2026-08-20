<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvDevicePreciseCluesAccount extends BaseModel {

    public function setCluesAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getCluesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
