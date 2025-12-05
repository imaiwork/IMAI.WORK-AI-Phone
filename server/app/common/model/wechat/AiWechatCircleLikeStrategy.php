<?php

namespace app\common\model\wechat;

use app\common\model\BaseModel;

class AiWechatCircleLikeStrategy extends BaseModel
{

    public function setTagIdsAttr($value)
    {
        if (is_array($value))
        {

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getTagIdsAttr($value): array
    {
        if (is_string($value))
        {
            return json_decode($value, true);
        }
        return [];
    }
}
