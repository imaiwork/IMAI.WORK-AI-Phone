<?php

namespace app\common\model\wechat;

use app\common\model\BaseModel;

class AiWechatAcceptFriendStrategy extends BaseModel
{
    public function setAcceptSourceAttr($value)
    {
        if (is_array($value))
        {

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getAcceptSourceAttr($value): array
    {
        if (is_string($value))
        {
            return json_decode($value, true);
        }
        return [];
    }


    public function setWechatIdsAttr($value)
    {
        if (is_array($value))
        {

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getWechatIdsAttr($value): array
    {
        if (is_string($value))
        {
            return json_decode($value, true);
        }
        return [];
    }
}
