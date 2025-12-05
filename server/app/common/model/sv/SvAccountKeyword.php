<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvAccountKeyword extends BaseModel
{

    public function setReplyAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getReplyAttr($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return [];
    }
}
