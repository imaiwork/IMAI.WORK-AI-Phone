<?php

namespace app\common\model\wechat;

use app\common\model\BaseModel;

class AiWechatSetting extends BaseModel
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
