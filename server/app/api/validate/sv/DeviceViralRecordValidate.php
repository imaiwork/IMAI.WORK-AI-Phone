<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 爆款库记录校验
 */
class DeviceViralRecordValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number',
        'ids' => 'require',
        'is_interested' => 'require|in:0,1',
        'rewritten_text' => 'require',
        'title' => 'max:255',
        'day' => 'date',
        'account_type' => 'in:1,3,4,5',
        'persona_id' => 'require|integer|gt:0',
        'share_content' => 'require|max:2000',
        'source' => 'require|in:auto,manual',
    ];

    protected $message = [
        'id.require' => '请输入记录ID',
        'id.number' => '记录ID必须是数字',
        'ids.require' => '请选择记录',
        'is_interested.require' => '请选择是否感兴趣',
        'is_interested.in' => '是否感兴趣值只能是0或1',
        'rewritten_text.require' => '仿写文案不能为空',
        'title.max' => '标题不能超过255个字符',
        'day.date' => '日期格式错误',
        'account_type.in' => '平台类型错误',
        'persona_id.require' => '请输入人设ID',
        'persona_id.integer' => '人设ID必须是整数',
        'persona_id.gt' => '人设ID必须大于0',
        'share_content.require' => '请粘贴分享链接',
        'share_content.max' => '分享内容不能超过2000个字符',
        'source.require' => '请传入来源 source',
        'source.in' => 'source只能是auto或manual',
    ];

    public function sceneLists()
    {
        return $this->only(['persona_id']);
    }

    public function sceneInterest()
    {
        return $this->only(['ids', 'is_interested', 'source']);
    }

    public function sceneClearUninterested()
    {
        return $this->only(['day', 'account_type']);
    }

    public function sceneSaveCopywriting()
    {
        return $this->only(['id', 'rewritten_text', 'title']);
    }

    public function sceneManualImport()
    {
        return $this->only(['persona_id', 'share_content']);
    }
}
