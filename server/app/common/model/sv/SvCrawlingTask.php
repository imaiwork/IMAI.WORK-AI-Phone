<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class SvCrawlingTask extends BaseModel {

    use SoftDelete;

    protected $deleteTime = 'delete_time';

    public function setWechatTimeConfigAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getWechatTimeConfigAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setWechatCustomDateAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getWechatCustomDateAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}