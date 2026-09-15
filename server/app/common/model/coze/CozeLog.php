<?php

namespace app\common\model\coze;

use app\common\model\BaseModel;

/**
 * Coze 配置模型
 */
class CozeLog extends BaseModel
{
    protected $name = 'coze_log';

    /**
     * 创建时间格式化
     */
    public function getCreateTimeAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 更新时间格式化
     */
    public function getUpdateTimeAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return date('Y-m-d H:i:s', $value);
    }
}


