<?php

namespace app\common\model\wechat;

use app\common\model\BaseModel;

class AiWechatTagStrategy extends BaseModel
{
 
    public function setMatchKeywordsAttr($value)
    {
        if (is_array($value)) {

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getMatchKeywordsAttr($value)
    {
        if (is_string($value)) {
            return implode(',', json_decode($value, true));
        }
        return '';
    }
}
