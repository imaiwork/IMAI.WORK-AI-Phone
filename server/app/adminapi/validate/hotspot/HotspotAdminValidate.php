<?php

namespace app\adminapi\validate\hotspot;

use app\common\validate\BaseValidate;

class HotspotAdminValidate extends BaseValidate
{
    protected $rule = [
        'platform' => 'in:douyin,kuaishou,xiaohongshu,weibo',
        'period' => 'in:day,week,rise',
        'day' => 'dateFormat:Y-m-d',
        'limit' => 'integer|between:1,100',
        'status' => 'in:script,video',
        'id' => 'require',
        'keyword' => 'max:64',
        'user' => 'max:64',
        'start_time' => 'date',
        'end_time' => 'date',
    ];

    protected $message = [
        'platform.in' => '不支持的平台',
        'period.in' => 'period 仅支持 day/week/rise',
        'day.dateFormat' => '日期格式必须为 Y-m-d',
        'limit.integer' => 'limit 必须是整数',
        'limit.between' => 'limit 必须在 1 到 100 之间',
        'status.in' => '状态值不正确',
        'id.require' => 'id参数缺失',
        'keyword.max' => '关键词不能超过 64 字',
        'user.max' => '用户搜索不能超过 64 字',
    ];

    public function sceneHot()
    {
        return $this->only(['platform', 'period', 'day', 'limit'])
            ->append('platform', 'require');
    }

    public function sceneHistoryDates()
    {
        return $this->only(['platform'])->append('platform', 'require');
    }

    public function sceneLists()
    {
        return $this->only(['platform', 'status', 'keyword', 'user', 'start_time', 'end_time']);
    }

    public function sceneTaskLists()
    {
        // 任务状态集合与创作记录不同（running/wait/done/fail vs script/video）。
        // 不用 remove/append 重定义 status 规则：该组合在不同框架小版本上行为不一致
        // （remove(field, true) 曾把 true 当数组做 in_array 直接 500），
        // status 合法性由 TaskLists::buildQuery 的白名单兜底，非法值按空处理
        return $this->only(['keyword', 'user', 'start_time', 'end_time']);
    }

    public function sceneId()
    {
        return $this->only(['id']);
    }
}
