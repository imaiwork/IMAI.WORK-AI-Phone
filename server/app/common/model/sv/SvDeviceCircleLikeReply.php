<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvDeviceCircleLikeReply extends BaseModel {

    public function setAccountsAttr($value)
    {
        if (is_array($value))
        {

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getAccountsAttr($value)
    {
        if (is_string($value))
        {
            return json_decode($value, true);
        }
        return [];
    }



    public function setTimeConfigAttr($value)
    {
        if (is_array($value))
        {

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getTimeConfigAttr($value)
    {
        if (is_string($value))
        {
            return json_decode($value, true);
        }
        return [];
    }
}
