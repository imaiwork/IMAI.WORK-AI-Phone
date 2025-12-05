<?php

namespace app\common\model\shanjian;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;


class ShanjianClipTemplate extends BaseModel
{
    public function getCreateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : null;
    }

    public function getUpdateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : null;
    }
}
