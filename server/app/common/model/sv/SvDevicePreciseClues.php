<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvDevicePreciseClues extends BaseModel {

    

    public function setAccountsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getAccountsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    
    public function setCustomDateAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getCustomDateAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setTimeConfigAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getTimeConfigAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
