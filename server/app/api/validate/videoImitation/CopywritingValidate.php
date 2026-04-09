<?php

namespace app\api\validate\videoImitation;

use app\common\validate\BaseValidate;

/**
 * 视频仿写文案校验
 * Class CopywritingValidate
 * @package app\api\validate\videoImitation
 */
class CopywritingValidate extends BaseValidate
{
    protected $rule = [
        'url' => 'require',
        'id'  => 'integer|min:0',
        'persona_id' => 'require|integer'
    ];

    protected $message = [
        'url.require' => '参数 url 不能为空',
        'id.integer'  => 'ID必须是整数',
        'id.min'      => 'ID不能小于0',
        'persona_id.require' => '必须选择IP人设',
        'persona_id.integer' => '人设ID格式不正确'
    ];

    public function sceneVideo2text()
    {
        return $this->only(['url', 'id', 'persona_id']);
    }
}
