<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvDeviceViralAccount extends BaseModel
{
    public function setKeywordsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getKeywordsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
