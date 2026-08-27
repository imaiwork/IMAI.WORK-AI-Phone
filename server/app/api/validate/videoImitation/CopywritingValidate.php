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
        'persona_id' => 'require|integer',
        'visual_material_source' => 'in:1,2,3',
        'rewrite_mode' => 'in:1,2',
    ];

    protected $message = [
        'url.require' => '参数 url 不能为空',
        'id.integer'  => 'ID必须是整数',
        'id.min'      => 'ID不能小于0',
        'persona_id.require' => '必须选择IP人设',
        'persona_id.integer' => '人设ID格式不正确',
        'rewrite_mode.in' => '改写模式仅支持1（人设复刻）或2（洗稿）',
    ];

    public function sceneVideo2text()
    {
        return $this->only(['url', 'id', 'persona_id', 'visual_material_source', 'rewrite_mode']);
    }

    public function sceneImage2text()
    {
        return $this->only(['url', 'id', 'persona_id', 'rewrite_mode']);
    }
}
