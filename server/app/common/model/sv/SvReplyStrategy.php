<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvReplyStrategy extends BaseModel
{

    public function setStopKeywordsAttr($value)
    {
        if (is_array($value)) {

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getStopKeywordsAttr($value)
    {
        if (is_string($value)) {
            return implode(';', json_decode($value, true));
        }
        return '';
    }
}
