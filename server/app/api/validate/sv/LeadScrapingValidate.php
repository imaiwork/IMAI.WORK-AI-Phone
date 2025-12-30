<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 截流设置校验
 * Class LeadScrapingValidate
 * @package app\api\validate\sv
 */
class LeadScrapingValidate extends BaseValidate
{

    protected $rule = [
        'id'              => 'require',
        'task_type'       => 'require',
        'name'            => 'require',
        'accounts'        => 'require',
        'task_start_time' => 'require',
        'task_end_time'   => 'require',
        'status'          => 'require',
    ];


    protected $message = [
        'id.require'              => '请输入主键ID',
        'task_type.require'       => '请输入截流任务类型：1评论、2私信',
        'name.require'            => '请输入截流任务名称',
        'accounts.require'        => '请选择账号',
        'task_start_time.require' => '请输入截流开始时间',
        'task_end_time.require'   => '请输入截流结束时间',
        'status.require'          => '缺少状态参数',
    ];


    /**
     * @notes 添加
     * @return Validate
     */
    public function sceneAdd()
    {
        return $this->only(['task_type']);
    }

    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 状态修改
     * @return Validate
     */
    public function sceneChange()
    {
        return $this->only(['id', 'status']);
    }

    /**
     * @notes 删除
     * @return Validate
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 详情
     * @return Validate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 详情
     * @return Validate
     */
    public function sceneRecordList()
    {
        return $this->only(['id']);
    }
}
