<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvSetting extends BaseModel
{

    /**
     * 获取tags
     */
    public function getTagsAttr($value)
    {
        if ($value) {
            return json_decode($value, true);
        }
        return [];
    }

    /**
     * 设置tags
     */
    public function setTagsAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }
        return json_encode([]);
    }
}
