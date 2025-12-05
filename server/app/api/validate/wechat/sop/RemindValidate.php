<?php
declare (strict_types = 1);

namespace app\api\validate\wechat\sop;

use app\common\validate\BaseValidate;
use think\exception\HttpResponseException;
use think\facade\Db;

class RemindValidate extends BaseValidate
{
    protected $rule = [
        'flow_id' => 'integer',
        'stage_id' => 'integer',
        'content' => 'max:300',
        'status' => 'in:0,1',
        'judgment' => 'integer',
        'send_time' => 'dateFormat:H:i:s',
    ];

    protected $message = [
        'flow_id.require' => '请输入流程ID',
        'stage_id.require' => '请输入阶段ID',
        'content.require' => '请输入内容',
        'content.max' => '内容最大长度为300个字符',
        'status.require' => '请输入状态',
        'status.in' => '状态值不正确',
        'judgment.require' => '请输入判断时间',
        'judgment.integer' => '判断时间必须为整数',
        'send_time.require' => '请输入发送时间',
        'send_time.dateFormat' => '发送时间格式不正确',
    ];

    /**
     * 检查内容是否在同一流程中重复
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    protected function checkContentUnique($value, $rule, array $data): bool|string
    {
       
        $where = [
            ['content', '=', $value],
            ['stage_id', '=', $data['stage_id']],
            ['status', '=', $data['status']],
            ['delete_time', 'null', null]
        ];

        // 如果是更新操作，排除自身
        if (!empty($data['id'])) {
            $where[] = ['id', '<>', $data['id']];
        }

        $exists = Db::name('ai_wechat_sop_sub_flow_remind')->where($where)->find();
        return $exists ? '该流程下已存在相同的内容' : true;
    }

    /**
     * @notes 创建跟进提醒
     */
    public function sceneCreateRemind()
    {
        return $this->only(['flow_id', 'stage_id', 'content', 'status', 'judgment', 'send_time'])
            ->append('content', 'require|max:300|checkContentUnique')
            ->append('stage_id', 'require')
            ->append('flow_id', 'require');
    }

    /**
     * @notes 更新跟进提醒
     */
    public function sceneUpdateRemind()
    {
        return $this->only(['id', 'content', 'status', 'judgment', 'send_time']);
    }

    /**
     * @notes 删除跟进提醒
     */
    public function sceneDeleteRemind()
    {
        return $this->only(['id']);
    }

  
} 