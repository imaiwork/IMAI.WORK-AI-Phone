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
        'url' => 'require|checkShareLink',
        'id'  => 'integer|min:0',
        'persona_id' => 'require|integer',
        'visual_material_source' => 'in:1,2,3',
        'rewrite_mode' => 'in:1,2',
        // 目标字数档位，取值见 VideoImitationTask::COPYWRITING_LENGTH_OPTIONS
        'copywriting_length' => 'in:100,200,350,600',
    ];

    protected $message = [
        'url.require' => '参数 url 不能为空',
        'id.integer'  => 'ID必须是整数',
        'id.min'      => 'ID不能小于0',
        'persona_id.require' => '必须选择IP人设',
        'persona_id.integer' => '人设ID格式不正确',
        'rewrite_mode.in' => '改写模式仅支持1（人设复刻）或2（洗稿）',
        'copywriting_length.in' => '文案长度仅支持约100/200/350/600字',
    ];

    public function sceneVideo2text()
    {
        return $this->only(['url', 'id', 'persona_id', 'visual_material_source', 'rewrite_mode', 'copywriting_length']);
    }

    public function sceneImage2text()
    {
        return $this->only(['url', 'id', 'persona_id', 'rewrite_mode']);
    }

    /**
     * 分享口令里必须含 http(s) 链接。
     *
     * 不校验的话，任意文本都能建单：任务照常异步跑一遍，几分钟后以「该链接内容无法提取」
     * 收场，用户完全看不出是自己粘错了内容（实例：test 任务 764 粘进去的是一段问题描述）。
     * 口径与中台 extractVideoImitationURL 的 `https?://\S+` 一致，口令里夹着链接也放行。
     */
    protected function checkShareLink($value, $rule, $data)
    {
        if (preg_match('/https?:\/\/\S+/i', (string)$value) !== 1) {
            return '没有识别到作品链接，请粘贴完整的分享口令或以 http 开头的链接';
        }

        return true;
    }
}
