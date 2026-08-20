<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

use think\model\concern\SoftDelete;
class SvDeviceTakeOverTask extends BaseModel {

    protected $deleteTime = 'delete_time';

    // public static function getCommentRobotPrompt()
    // {
    //     return '我是智能体，我会根据您的指令进行回复。';
    // }

    
    public function setMessageSpeechAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getMessageSpeechAttr($value)
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
}
