<?php
declare (strict_types=1);

namespace app\api\validate\wechat\sop;

use app\common\model\wechat\sop\AiWechatSopFlow;
use app\common\model\wechat\sop\AiWechatSopSubStage;
use app\common\validate\BaseValidate;

class PushMemberValidate extends BaseValidate
{
    protected $rule = [
        'flow_id'   => 'require|number|checkFlow',
        'stage_id'  => 'require|number|checkStage',
        'wechat_id' => 'require',
        'friend_id' => 'require'
    ];

    protected $message = [
        'wechat_id.require'   => '微信号不能为空',
        'friend_id.require'   => '人员信息错误',
        'flow_id.require'     => '流程ID不能为空',
        'stage_id.require'    => '阶段ID不能为空',
        'to_flow_id.require'  => '转移流程ID不能为空',
        'to_stage_id.require' => '转移阶段ID不能为空',
    ];

    public function sceneTransfer()
    {
        return $this->append('to_flow_id', 'require|checkToFlow')
                    ->append('to_stage_id', 'require|checkToStage');
    }

    protected function checkFlow($value, $rule, array $data)
    {
        $where = [
            ['id', '=', $value],
            ['user_id', '=', $this->request->userId],
        ];
        $exists = AiWechatSopFlow::where($where)->find();
        return $exists ? true : '流程ID不存在';
    }

    protected function checkStage($value, $rule, array $data)
    {
        $where = [
            ['id', '=', $value],
            ['user_id', '=', $this->request->userId],
            ['flow_id', '=', $data['flow_id']],
        ];
        $exists = AiWechatSopSubStage::where($where)->find();
        return $exists ? true : '阶段ID不存在';
    }

    protected function checkToFlow($value, $rule, array $data)
    {
        $where = [
            ['id', '=', $value],
            ['user_id', '=', $this->request->userId],
        ];
        $exists = AiWechatSopFlow::where($where)->find();
        return $exists ? true : '转移流程ID不存在';
    }

    protected function checkToStage($value, $rule, array $data)
    {
        $where = [
            ['id', '=', $value],
            ['user_id', '=', $this->request->userId],
            ['flow_id', '=', $data['to_flow_id']],
        ];
        $exists = AiWechatSopSubStage::where($where)->find();
        return $exists ? true : '转移阶段ID有误';
    }
} 