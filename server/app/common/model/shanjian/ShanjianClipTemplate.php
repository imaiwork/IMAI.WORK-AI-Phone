<?php

namespace app\common\model\shanjian;

use app\common\model\BaseModel;


class ShanjianClipTemplate extends BaseModel
{
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    public function getCreateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : null;
    }

    public function getUpdateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : null;
    }
}
