<?php

namespace app\common\model\wechat;

use app\common\model\BaseModel;

class AiWechatReplyStrategy extends BaseModel
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
        if (is_array($value)) {
            return implode(';', $value);
        }
        if (!is_string($value) || $value === '') {
            return '';
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $value;
        }

        return is_array($decoded) ? implode(';', $decoded) : (string)$decoded;
    }
}
