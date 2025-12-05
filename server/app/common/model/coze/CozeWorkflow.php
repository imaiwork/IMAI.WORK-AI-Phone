<?php

namespace app\common\model\coze;

use app\common\model\BaseModel;

/**
 * Coze 工作流模型
 */
class CozeWorkflow extends BaseModel
{
    protected $name = 'coze_workflow';

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


