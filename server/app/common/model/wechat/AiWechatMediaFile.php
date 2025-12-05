<?php

namespace app\common\model\wechat;

use app\common\model\BaseModel;

class AiWechatMediaFile extends BaseModel
{

    public function setGroupIdsAttr($value)
    {
        if (is_array($value))
        {
            return json_encode($value);
        }
        return json_encode([]);
    }


    public function getGroupIdsAttr($value)
    {
        if (is_string($value))
        {
            return json_decode($value, true);
        }
        return [];
    }

    public function setExtInfoAttr($value)
    {
        if (is_array($value))
        {
            return json_encode($value);
        }
        return json_encode([]);
    }

    public function getExtInfoAttr($value)
    {
        if (is_string($value))
        {
            return json_decode($value, true);
        }
        return [];
    }
}
