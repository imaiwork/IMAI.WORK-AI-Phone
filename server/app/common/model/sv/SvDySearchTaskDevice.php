<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class SvDySearchTaskDevice extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    public function setKeywordsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getKeywordsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
