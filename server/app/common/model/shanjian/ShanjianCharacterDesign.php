<?php

namespace app\common\model\shanjian;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * 人设模型
 * Class ShanjianCharacterDesign
 */
class ShanjianCharacterDesign extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    public function getCreateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : null;
    }

    public function getUpdateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : null;
    }
}


