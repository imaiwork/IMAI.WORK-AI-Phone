<?php

namespace app\common\model\wechat;

use app\common\model\BaseModel;

class AiWechatCircleTask extends BaseModel
{

    public function setCommentAttr($value)
    {
        if (is_array($value))
        {

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getCommentAttr($value)
    {
        if (is_string($value))
        {
            return implode('##', json_decode($value, true));
        }
        return '';
    }

    public function setAttachmentContentAttr($value)
    {
        if (is_array($value))
        {

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getAttachmentContentAttr($value)
    {
        if (is_string($value))
        {
            return json_decode($value, true);
        }
        return [];
    }
}
