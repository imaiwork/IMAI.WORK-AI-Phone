<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvDevicePreciseCluesRecord extends BaseModel
{
    public function setExtraAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getExtraAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setRawPayloadAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getRawPayloadAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setExceptionLogAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getExceptionLogAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
