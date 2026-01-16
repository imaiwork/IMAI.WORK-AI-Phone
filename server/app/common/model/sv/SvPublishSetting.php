<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

use think\model\concern\SoftDelete;

class SvPublishSetting extends BaseModel {
    use SoftDelete;
    protected $deleteTime = 'delete_time';

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
