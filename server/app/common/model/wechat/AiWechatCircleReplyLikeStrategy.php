<?php

namespace app\common\model\wechat;

use app\common\model\BaseModel;

class AiWechatCircleReplyLikeStrategy extends BaseModel

{
    public function setReplyTagIdsAttr($value)
    {
        if (is_array($value))
        {

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getReplyTagIdsAttr($value): array
    {
        if (is_string($value))
        {
            return json_decode($value, true);
        }
        return [];
    }

    public function setLikeTagIdsAttr($value)
    {
        if (is_array($value))
        {

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getLikeTagIdsAttr($value): array
    {
        if (is_string($value))
        {
            return json_decode($value, true);
        }
        return [];
    }
}
