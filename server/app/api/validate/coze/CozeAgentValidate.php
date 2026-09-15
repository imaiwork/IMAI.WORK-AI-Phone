<?php

namespace app\api\validate\coze;

use app\common\validate\BaseValidate;

class CozeAgentValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number',
        'type' => 'in:1,2',
        'bg_image' => 'max:255',
        'avatar' => 'max:255',
        'name' => 'require|max:255',
        'agent_cate_id' => 'require',
        'permissions' => 'in:0',
        'introduced' => 'max:65535',
        'stream' => 'in:0,1',
        'deduction' => 'in:0,1',
        'tokens' => 'float|egt:0',
        'coze_id' => 'require|max:255',
    ];

    protected $message = [
        'id.require' => 'ID是必填项',
        'id.number' => 'ID必须为数字',
        'type.in' => '类型只能是1或2',
        'bg_image.max' => '背景图路径不能超过255个字符',
        'avatar.max' => '头像路径不能超过255个字符',
        'name.require' => '名称是必填项',
        'name.max' => '名称不能超过255个字符',
        'agent_cate_id.require' => 'agent_cate_id是必填项',
        'permissions.in' => '权限类型只能是0',
        'introduced.max' => '介绍不能超过65535个字符',
        'stream.in' => '输出类型只能是0或1',
        'deduction.in' => '扣费类型只能是0或1',
        'tokens.float' => '扣费算力必须是数字',
        'tokens.egt' => '扣费算力不能小于0',
        'coze_id.require' => 'coze_id是必填项',
        'coze_id.max' => 'coze_id不能超过255个字符',
    ];

    public function sceneAdd()
    {
        return $this->only(['type', 'bg_image', 'avatar', 'name', 'permissions', 'introduced', 'stream', 'deduction', 'tokens', 'coze_id'])
            ->append('name', 'require')
            ->append('coze_id', 'require');
    }

    public function sceneUpdate()
    {
        return $this->only(['id', 'type', 'bg_image', 'avatar', 'name', 'permissions', 'introduced', 'stream', 'deduction', 'tokens', 'coze_id']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

}
