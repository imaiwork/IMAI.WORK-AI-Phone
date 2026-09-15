<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class SvDySearchTask extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    public function setScanConfigAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getScanConfigAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setKeywordsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getKeywordsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setDeviceCodesAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getDeviceCodesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setRemarksAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getRemarksAttr($value)
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

    public function setCustomDateAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getCustomDateAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
