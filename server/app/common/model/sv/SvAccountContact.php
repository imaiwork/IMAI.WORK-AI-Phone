<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvAccountContact extends BaseModel
{

    // 关闭自动时间维护
    protected $autoWriteTimestamp = false;

    /**
     * 获取标签ID
     */
    public function getLabelIdsAttr($value)
    {
        if ($value) {
            return json_decode($value, true);
        }
        return [];
    }

    /**
     * 设置标签ID
     */
    public function setLabelIdsAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }
        return json_encode([]);
    }
}
