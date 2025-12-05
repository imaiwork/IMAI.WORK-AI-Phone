<?php


namespace app\api\validate\kb;

use app\common\validate\BaseValidate;

/**
 * 机器人对话参数验证器
 */
class KbChatValidate extends BaseValidate
{
    protected $rule = [
        'robot_id' => 'require|number',
        'cate_id'  => 'require|number',
        'model'    => 'require',
        'question' => 'require|max:8000'

    ];

    protected $message = [
        'robot_id.require'  => '请选择机器人',
        'cate_id.require'   => '请选择会话窗口',
        'model.require'     => '请配置对话模型',
        'question.require'  => '提问问题不可为空',
        'question.max'      => '问题太长了缩减到8000个字符内吧'
    ];
}