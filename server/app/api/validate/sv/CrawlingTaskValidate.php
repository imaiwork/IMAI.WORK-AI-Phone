<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 爬取任务校验
 * Class CrawlingTaskValidate
 */
class CrawlingTaskValidate extends BaseValidate
{
    protected $rule = [
        'id'                             => 'require',
        'user_id'                        => 'require',
        'device_code'                    => 'max:255',
        'device_codes'                   => 'require',
        'task_id'                        => 'require|max:50',
        'type'                           => 'require',
        'keywords'                       => 'require',
        'implementation_keywords_number' => 'number',
        'chat_type'                      => 'require|in:0,1',
        'chat_number'                    => 'number|checkChatNumber',
        'chat_interval_time'             => 'number|checkChatIntervalTime',
        'add_type'                       => 'require|in:0,1',
        'status'                         => 'require|in:0,1,2,3',
        'remark'                         => 'max:255|checkRemark',
        'add_number'                     => 'number|checkAddNumber',
        'add_interval_time'              => 'number|checkAddIntervalTime',
        'greeting_content'               => 'max:255|checkGreetingContent',
        'implementation_number'          => 'number',
        'number_of_implemented'          => 'number',
    ];

    protected $message = [
        'id.require'                               => '请输入主键ID',
        'user_id.require'                          => '请输入用户ID',
        'task_id.require'                          => '请输入唯一任务ID',
        'type.require'                             => '请输入账号类型',
        'keywords.require'                         => '请输入检索关键词',
        'device_codes.require'                     => '请选择设备',
        'chat_type.require'                        => '请输入自动私聊类型',
        'chat_type.in'                             => '自动私聊类型值不正确',
        'chat_number.number'                       => '私聊数量必须为数字',
        'chat_number.checkChatNumber'              => '当自动私聊类型为1时，私聊数量为必填项',
        'chat_interval_time.number'                => '私聊间隔时间必须为数字',
        'chat_interval_time.checkChatIntervalTime' => '当自动私聊类型为1时，私聊间隔时间为必填项',
        'add_type.require'                         => '请输入自动加好友类型',
        'add_type.in'                              => '自动加好友类型值不正确',
        'remark.checkRemark'                       => '当自动加好友类型为1时，备注为必填项',
        'add_number.checkAddNumber'                => '当自动加好友类型为1时，添加数量为必填项',
        'add_interval_time.checkAddIntervalTime'   => '当自动加好友类型为1时，添加间隔时间为必填项',
        'greeting_content.checkGreetingContent'    => '当自动加好友类型为1时，打招呼为必填项',
        'status.require'                           => '请输入状态',
        'status.in'                                => '状态值不正确',
    ];

    protected function checkChatNumber($value, $rule, $data = [])
    {
        if (isset($data['chat_type']) && $data['chat_type'] == 1) {
            return !empty($value);
        }
        return true;
    }

    // 验证chat_type=1时必填
    protected function checkChatIntervalTime($value, $rule, $data = [])
    {
        if (isset($data['chat_type']) && $data['chat_type'] == 1) {
            return !empty($value);
        }
        return true;
    }

    // 验证add_type=1时必填
    protected function checkRemark($value, $rule, $data = [])
    {
        if ($data['add_type'] == 1) {
            return !empty($value);
        }
        return true;
    }

    // 验证add_type=1时必填
    protected function checkAddNumber($value, $rule, $data = [])
    {
        if ($data['add_type'] == 1) {
            return !empty($value);
        }
        return true;
    }

    // 验证add_type=1时必填
    protected function checkAddIntervalTime($value, $rule, $data = [])
    {
        if ($data['add_type'] == 1) {
            return !empty($value);
        }
        return true;
    }

    // 验证add_type=1时必填
    protected function checkGreetingContent($value, $rule, $data = [])
    {
        if ($data['add_type'] == 1) {
            return !empty($value);
        }
        return true;
    }

    // 添加场景
    public function sceneAdd()
    {
        return $this->only([
                               'type',
                               'keywords',
                               'chat_type',
                               'chat_number',
                               'chat_interval_time',
                               'add_type',
                               'remark',
                               'add_number',
                               'add_interval_time',
                               'greeting_content'
                           ]);
    }

    // 更新场景
    public function sceneUpdate()
    {
        return $this->only([
                               'id',
//                               'chat_type',
//                               'chat_number',
//                               'chat_interval_time',
//                               'add_type',
//                               'remark',
//                               'add_number',
//                               'add_interval_time',
//                               'greeting_content'
                           ]);
    }

    // 详情场景
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    // 删除场景
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    // 删除设备场景
    public function sceneDeleteDevice()
    {
        return $this->only(['id','device_code']);
    }

    // 公共场景
    public function sceneCommon()
    {
        return $this->only(['id']);
    }
}