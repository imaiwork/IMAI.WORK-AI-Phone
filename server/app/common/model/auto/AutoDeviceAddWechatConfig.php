<?php

namespace app\common\model\auto;

use app\common\model\BaseModel;

class AutoDeviceAddWechatConfig extends BaseModel {
    public function setRemarksAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getRemarksAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}