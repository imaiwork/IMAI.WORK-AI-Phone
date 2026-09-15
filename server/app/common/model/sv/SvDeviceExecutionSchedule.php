<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvDeviceExecutionSchedule extends BaseModel {


    public function setPlatformAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getPlatformAttr($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return [];
    }
}
