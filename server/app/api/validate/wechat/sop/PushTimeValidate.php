<?php
declare (strict_types = 1);

namespace app\api\validate\wechat\sop;

use app\common\validate\BaseValidate;

class PushTimeValidate extends BaseValidate
{
    protected $rule = [
        'push_id' => 'require|integer',
        'push_time' => 'dateFormat:H:i:s',
        'order_day' => 'require|integer',
        // 其他验证规则
    ];

    protected $message = [
        'push_id.require' => '推送ID不能为空',
        'push_time.require' => '推送时间不能为空',
        'push_time.dateFormat' => '推送时间格式不正确',
        'order_day.require' => '排序天数不能为空',
        'order_day.integer' => '排序天数必须为整数',
        // 其他错误信息
    ];

    public function sceneCreatePushTime()
    {
        return $this->only(['push_id', 'order_day', 'push_time'])
                    ->append('order_day', 'require|integer')
                    ->append('push_time', 'require|dateFormat:H:i:s');
    }

    public function sceneUpdatePushTime()
    {
        return $this->only(['id', 'push_id', 'order_day', 'push_time'])
                    ->append('order_day', 'require|integer')
                    ->append('push_time', 'require|dateFormat:H:i:s');
    }

    public function sceneDeletePushTime()
    {
        return $this->only(['id']);
    }
} 