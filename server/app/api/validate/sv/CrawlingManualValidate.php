<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 爬取任务校验
 * Class CrawlingTaskValidate
 */
class CrawlingManualValidate extends BaseValidate
{
    protected $rule = [
        'source'                         => 'require|in:1,2',
        'add_type'                       => 'require|in:0,1',
        'status'                         => 'require|in:0,1,2,3,4',
        'add_number'                     => 'require',
        'add_interval_time'              => 'require',
        'add_remark_enable'              => 'require|in:0,1',
        'remarks'                        => 'require',
        'wechat_id'                      => 'require|max:1000',
        'wechat_reg_type'                => 'require|in:0,1,2',
    ];

    protected $message = [
        'source.require'                         => '请选择来源',
        'add_type.require'                     => '请选择自动加好友类型',
        'status.require'                         => '请输入状态',
        'add_number.require'                       => '添加数量不能为空',
        'add_interval_time.require'                => '添加间隔时间不能为空',
        'add_remark_enable.require'              => '请选择是否添加备注',
        'add_remark_enable.in'                   => '是否添加备注值不正确',
        'remarks.require'                        => '请选择备注',
        'wechat_id.require'                      => '请输入微信号',
        'wechat_id.max'                          => '微信号最多1000个字符',
        'wechat_reg_type.require'                => '请选择微信号注册类型',
        'wechat_reg_type.in'                     => '微信号注册类型值不正确',
        'add_type.in'                              => '自动加好友类型值不正确',
    ];

    // 添加场景
    public function sceneAdd()
    {
        return $this->only([
            'source',
            'crawling_task_ids',
            'add_type',
            'add_number',
            'add_interval_time',
            'add_friends_prompt',
            'add_remark_enable',
            'remarks',
            'wechat_id',
            'wechat_reg_type'
        ]);
    }

    // 详情场景
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneChange()
    {
        return $this->only(['id', 'status']);
    }

    // 删除场景
    public function sceneDelete()
    {
        return $this->only(['id']);
    }
    // 记录删除场景
    public function sceneRecordDelete()
    {
        return $this->only(['id']);
    }
}
