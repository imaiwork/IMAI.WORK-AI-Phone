<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class SvWechatStrategy extends BaseModel {

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

    public function setStopKeywordsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getStopKeywordsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}