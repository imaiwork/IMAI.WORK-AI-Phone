<?php

namespace app\api\validate\aiPersona;

use app\common\validate\BaseValidate;

/**
 * 人设内容记录 - 图片仿写记录校验
 */
class ImageRecordValidate extends BaseValidate
{
    protected $rule = [
        'ids' => 'require|array',
    ];

    protected $message = [
        'ids.require' => '请选择要删除的记录',
        'ids.array' => '删除ID必须是数组',
    ];

    public function sceneBatchDelete()
    {
        return $this->only(['ids']);
    }
}
