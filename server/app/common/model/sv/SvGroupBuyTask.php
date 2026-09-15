<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class SvGroupBuyTask extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

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

    public function setCommentKeywordAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getCommentKeywordAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setFilterAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getFilterAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setNicknameFilterAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getNicknameFilterAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }


    public function setOldAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getOldAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }


}