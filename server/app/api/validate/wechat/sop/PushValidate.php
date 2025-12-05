<?php
declare (strict_types=1);

namespace app\api\validate\wechat\sop;

use app\common\validate\BaseValidate;
use think\facade\Db;

class PushValidate extends BaseValidate
{
    protected $rule = [
        'id'        => 'number',
        'push_name' => 'max:60',
        'status'    => 'in:0,1,2',
        'type'      => 'integer',
        'push_type' => 'in:0,1'
    ];

    protected $message = [
        'id.require'        => '请输入推送ID',
        'push_name.require' => '请输入推送名称',
        'push_name.max'     => '推送名称最大长度为60个字符',
        'status.require'    => '请输入状态',
        'status.in'         => '状态值不正确',
        'type.require'      => '请输入类型',
        'push_type.require' => '请输入推送类型',
        'push_type.in'      => '推送类型值不正常',
        'flow_id.require'   => '请选择流程',
        'stage_id.require'  => '请选择阶段',
    ];

    /**
     * @notes 创建推送
     */
    public function sceneCreatePush()
    {
        return $this->only(['push_name', 'status', 'type', 'push_type']) // 确保包含 push_type
                    ->append('push_name', 'require')
                    ->append('flow_id', 'requireIf:type,1|2')
                    ->append('stage_id', 'requireIf:type,1|2')
                    ->append('push_type', 'require')
                    ->append('push_type', 'in:0,1'); // 确保校验规则正确
    }

    /**
     * @notes 更新推送
     */
    public function sceneUpdatePush()
    {
        return $this->only(['id', 'push_name', 'status', 'type'])
                    ->remove('push_name')
                    ->append('id', 'require')
                    ->append('flow_id', 'requireIf:type,1')
                    ->append('flow_id', 'requireIf:type,2')
                    ->append('stage_id', 'requireIf:type,1|2')
                    ->remove('flow_id', 'requireIf:type,0')
                    ->remove('flow_id', 'requireIf:type,3')
                    ->remove('flow_id', 'requireIf:type,4')
                    ->remove('stage_id', 'requireIf:type,3')
                    ->remove('stage_id', 'requireIf:type,4');
    }


    public function sceneUpdateSequencePush()
    {
        return $this->only(['id', 'push_name']);
    }

    /**
     * @notes 删除推送
     */
    public function sceneDeletePush()
    {
        return $this->only(['id']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneChoiceFlow()
    {
        return $this->only(['id'])->append('flow_id', 'require');
    }
} 