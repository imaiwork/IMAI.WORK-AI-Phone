<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 同城曝光任务校验
 */
class CityExposureTaskValidate extends BaseValidate
{
    protected $rule = [
        'id'              => 'require|integer',
        'name'            => 'require|max:255',
        'accounts'        => 'require',
        'task_start_time' => 'require|integer',
        'task_end_time'   => 'require|integer',
        'status'          => 'integer',
        'radius'          => 'integer|egt:0',
        'interval_time'   => 'integer|egt:1',
        'visit_num'       => 'integer|egt:1',
        'minutes'         => 'integer|egt:1',
    ];

    protected $message = [
        'id.require'              => '请传入任务ID',
        'id.integer'              => '任务ID格式错误',
        'name.require'            => '请输入任务名称',
        'name.max'                => '任务名称不能超过255个字符',
        'accounts.require'        => '请选择执行账号',
        'task_start_time.require' => '请选择任务开始时间',
        'task_end_time.require'   => '请选择任务结束时间',
        'radius.egt'              => '距离范围不能为负数',
        'interval_time.egt'       => '触达间隔至少为1秒',
        'visit_num.egt'           => '访问数至少为1',
        'minutes.egt'             => '执行时长至少为1分钟',
    ];

    protected $scene = [
        'add'          => ['name', 'accounts'],
        'edit'         => ['id', 'name'],
        'delete'       => ['id'],
        'updateStatus' => ['id'],
        'detail'       => ['id'],
    ];
}