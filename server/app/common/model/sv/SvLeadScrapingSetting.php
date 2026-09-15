<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class SvLeadScrapingSetting extends BaseModel {
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    /** 任务表 touch_type：1=固定话术 2=AI回复（与人设 shutoff_msg_type 相反，禁止原样拷贝） */
    public const TOUCH_TYPE_SPEECH = 1;
    public const TOUCH_TYPE_AI = 2;
    
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
    public function setTaskDateAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getTaskDateAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
    
}
