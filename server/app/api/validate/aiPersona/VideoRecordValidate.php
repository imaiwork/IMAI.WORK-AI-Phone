<?php

namespace app\api\validate\aiPersona;

use app\common\validate\BaseValidate;

/**
 * 人设内容记录 - 视频生成记录校验
 */
class VideoRecordValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number',
    ];

    protected $message = [
        'id.require' => '请输入视频任务ID',
        'id.number' => '视频任务ID必须是数字',
    ];

    public function sceneRetry()
    {
        return $this->only(['id']);
    }
}
