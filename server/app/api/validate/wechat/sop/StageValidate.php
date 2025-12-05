<?php
declare (strict_types = 1);

namespace app\api\validate\wechat\sop;

use app\common\model\wechat\sop\AiWechatSopSubStage;
use app\common\validate\BaseValidate;
use think\facade\Db;

class StageValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number',
        'flow_id' => 'require|number',
        'sub_stage_name' => 'max:8|chsAlpha|checkSubStageName',
        'status' => 'in:0,1,2',
        'sort' => 'number'
    ];

    protected $message = [
        'id.require' => '请输入ID',
        'flow_id.require' => '请输入流程ID',
        'sub_stage_name.max' => '阶段名称最大长度为8个字符',
        'sub_stage_name.chsAlpha' => '阶段名称只能是汉字或字母',
        'status.in' => '状态值不正确',
        'sort.number' => '排序值必须为数字'
    ];

    /**
     * 检查子阶段名称是否在同一个流程中重复
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    protected function checkSubStageName($value, $rule, array $data): bool|string
    {
        // 如果是更新操作，需要先获取当前阶段的flow_id
        if (!empty($data['id'])) {
            $stage = AiWechatSopSubStage::where('id', $data['id'])->find();
            if (!$stage) {
                return '子阶段不存在';
            }
            $flowId = $stage['flow_id'];
        } else {
            $flowId = $data['flow_id'];
        }

        $where = [
            ['sub_stage_name', '=', $value],
            ['flow_id', '=', $flowId],
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
     * @notes 创建子阶段
     */
    public function sceneCreateStage()
    {
        return $this->only(['flow_id', 'sub_stage_name', 'sort'])
            ->append('sub_stage_name', 'require|max:8|chsAlpha|checkSubStageName');
    }

    /**
     * @notes 更新子阶段
     */
    public function sceneUpdateStage()
    {
        return $this->only(['id', 'sub_stage_name', 'sort']);
    }

    /**
     * @notes 删除子阶段
     */
    public function sceneDeleteStage()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 获取子阶段列表
     */
    public function sceneStageLists()
    {
        return $this->only(['flow_id']);
    }
} 