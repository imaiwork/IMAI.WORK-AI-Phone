<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 团购截流任务校验
 * Class GroupBuyTaskValidate
 * @package app\api\validate\sv
 */
class GroupBuyTaskValidate extends BaseValidate
{
    protected $rule = [
        'id'              => 'require|integer',
        'task_type'       => 'require|integer|in:1,2',
        'name'            => 'require|max:255',
        'accounts'        => 'require',
        'task_start_time' => 'require|integer',
        'task_end_time'   => 'require|integer',
        'status'          => 'integer',
        'radius'          => 'integer|egt:0',
        'interval_time'   => 'integer|egt:1',
        'watch_time'      => 'integer|egt:1',
        'comment_offset'  => 'integer|egt:0',
    ];

    protected $message = [
        'id.require'              => '请传入任务ID',
        'id.integer'              => '任务ID格式错误',
        'task_type.require'       => '请选择任务类型',
        'task_type.in'            => '任务类型只能为1(收藏夹团购)或2(搜索团购)',
        'name.require'            => '请输入任务名称',
        'name.max'                => '任务名称不能超过255个字符',
        'accounts.require'        => '请选择执行账号',
        'task_start_time.require' => '请选择任务开始时间',
        'task_end_time.require'   => '请选择任务结束时间',
        'radius.integer'          => '距离范围必须为整数',
        'radius.egt'              => '距离范围不能为负数',
        'interval_time.egt'       => '触达间隔至少为1秒',
        'watch_time.egt'          => '观看时长至少为1秒',
        'comment_offset.egt'      => '评论起始位置不能为负数',
    ];

    protected $scene = [
        'add'          => ['accounts'],
        'edit'         => ['id'],
        'delete'       => ['id'],
        'updateStatus' => ['id'],
        'detail'       => ['id'],
    ];
}