<?php

namespace app\api\validate\copywriting;

use app\common\validate\BaseValidate;

class PublicCopyValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'persona_id' => 'require|integer|gt:0',
        'ids' => 'require|array',
        'library_type' => 'in:0,1,2',
        'driver_type' => 'in:0,1,2,3',
        'force' => 'in:0,1',
        'page_no' => 'integer|gt:0',
        'page_size' => 'integer|gt:0',
    ];

    protected $message = [
        'id.require' => '文案不能为空',
        'persona_id.require' => '人设不能为空',
        'ids.require' => '没有选中任何文案',
        'ids.array' => '文案id必须是数组',
    ];

    public function sceneBrowse()
    {
        return $this->only(['persona_id', 'library_type', 'driver_type', 'page_no', 'page_size']);
    }

    public function sceneAdd()
    {
        return $this->only(['persona_id', 'ids']);
    }

    public function sceneRefresh()
    {
        return $this->only(['id', 'force']);
    }
}
