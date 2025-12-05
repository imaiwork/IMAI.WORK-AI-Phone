<?php

namespace app\common\model\wechat;

use app\common\model\BaseModel;

class AiWechatGreetStrategy extends BaseModel
{

    public function setGreetContentAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }
        return json_encode([]);
    }


    public function getGreetContentAttr($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return [];
    }
}
