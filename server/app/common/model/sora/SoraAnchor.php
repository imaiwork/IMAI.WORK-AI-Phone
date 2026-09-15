<?php

namespace app\common\model\sora;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

/**
 * Sora角色
 * 对应表：iw_sora_anchor
 */
class SoraAnchor extends BaseModel
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


