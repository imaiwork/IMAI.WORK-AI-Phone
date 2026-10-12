<?php

namespace app\api\validate\square;

use app\common\validate\BaseValidate;

class SquareValidate extends BaseValidate
{
    protected $rule = [
        'persona_id' => 'require|integer|gt:0',
        'ids' => 'require|array',
        'group_id' => 'integer',
        'kind' => 'in:0,1,2',
        'page_no' => 'integer|gt:0',
        'page_size' => 'integer|gt:0',
    ];

    protected $message = [
        'persona_id.require' => '人设不能为空',
        'ids.require' => '没有选中任何素材',
        'ids.array' => '素材id必须是数组',
        'kind.in' => '素材类型只能是视频或图片',
    ];

    public function sceneBrowse()
    {
        return $this->only(['group_id', 'kind', 'page_no', 'page_size']);
    }

    public function sceneRefs()
    {
        return $this->only(['persona_id']);
    }

    public function sceneMutate()
    {
        return $this->only(['persona_id', 'ids']);
    }
}