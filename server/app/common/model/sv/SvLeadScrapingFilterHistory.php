<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class SvLeadScrapingFilterHistory extends BaseModel {
    public function setFilterAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getFilterAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setCommentSpeechAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getCommentSpeechAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
    
    
    public function setMsgSpeechAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getMsgSpeechAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }


    public function setMarkFilterAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getMarkFilterAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setMarkSpeechAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getMarkSpeechAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}


