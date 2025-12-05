<?php
declare (strict_types=1);

namespace app\api\validate\wechat\sop;

use app\common\validate\BaseValidate;
use think\facade\Db;

class FlowValidate extends BaseValidate
{
    protected $rule = [
        'id'             => 'require|number',
        'flow_id'        => 'require|number',
        'flow_name'      => 'require|max:15|chsAlpha|checkFlowName',
        'sub_stage_name' => 'require|max:8|chsAlpha|checkSubStageName',
        'status'         => 'in:0,1',
        'sort'           => 'number',
        'delete_time'    => 'number'
    ];

    protected $message = [
        'id.require'              => '请输入ID',
        'flow_id.require'         => '请输入流程ID',
        'flow_name.require'       => '请输入流程名称',
        'flow_name.max'           => '流程名称最大长度为15个字符',
        'flow_name.chsAlpha'      => '流程名称只能是汉字或字母',
        'sub_stage_name.require'  => '请输入阶段名称',
        'sub_stage_name.max'      => '阶段名称最大长度为8个字符',
        'sub_stage_name.chsAlpha' => '阶段名称只能是汉字或字母',
        'status.in'               => '状态值不正确',
        'delete_time.number'      => '删除时间格式不正确',
        'wechat_id.require'       => '请选择微信号',
    ];

    /**
     * 检查流程名称是否重复
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    protected function checkFlowName($value, $rule, array $data)
    {
        $where = [
            ['flow_name', '=', $value],
            ['delete_time', 'null', null]
        ];

        // 如果是更新操作，排除自身
        if (!empty($data['id'])) {
            $where[] = ['id', '<>', $data['id']];
        }

        $exists = Db::name('ai_wechat_sop_flow')->where($where)->find();
        return $exists ? '流程名称已存在' : true;
    }

    /**
     * 检查子阶段名称是否在同一个流程中重复
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    protected function checkSubStageName($value, $rule, array $data)
    {
        $where = [
            ['sub_stage_name', '=', $value],
            ['flow_id', '=', $data['flow_id']],
            ['delete_time', 'null', null]
        ];

        // 如果是更新操作，排除自身
        if (!empty($data['id'])) {
            $where[] = ['id', '<>', $data['id']];
        }

        $exists = Db::name('ai_wechat_sop_sub_stage')->where($where)->find();
        return $exists ? '该流程下已存在相同的阶段名称' : true;
    }

    /**
     * @notes 创建流程
     */
    public function sceneCreateFlow()
    {
        return $this->only(['flow_name']);
    }

    /**
     * @notes 创建子阶段
     */
    public function sceneCreateSubStage()
    {
        return $this->only(['flow_id', 'sub_stage_name', 'status', 'sort']);
    }

    /**
     * @notes 更新流程
     */
    public function sceneUpdateFlow()
    {
        return $this->only(['id', 'flow_name', 'status', 'delete_time']);
    }

    /**
     * @notes 更新子阶段
     */
    public function sceneUpdateSubStage()
    {
        return $this->only(['id', 'flow_id', 'sub_stage_name', 'sort']);
    }

    /**
     * @notes 删除流程
     */
    public function sceneDeleteFlow()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 删除子阶段
     */
    public function sceneDeleteSubStage()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 获取子阶段列表
     */
    public function sceneSubStageLists()
    {
        return $this->only(['flow_id']);
    }

    /**
     * @notes 创建流程
     */
    public function sceneDashboardFlow()
    {
        return $this->only(['flow_id', 'wechat_id'])
                    ->append('flow_id', 'require')
                    ->append('wechat_id', 'require');
    }
} 