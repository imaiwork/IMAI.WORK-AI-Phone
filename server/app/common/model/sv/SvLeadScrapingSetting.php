<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class SvLeadScrapingSetting extends BaseModel {
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    
    public function setIpAddressAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getIpAddressAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setMarkerMethodAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getMarkerMethodAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
