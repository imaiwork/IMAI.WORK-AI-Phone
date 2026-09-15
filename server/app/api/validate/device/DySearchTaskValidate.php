<?php

namespace app\api\validate\device;

use app\common\service\sv\DySearchAccountNormalizer;
use app\common\validate\BaseValidate;

/**
 * 抖音获客任务校验
 */
class DySearchTaskValidate extends BaseValidate
{
    protected $rule = [
        'id'           => 'require',
        'name'         => 'require',
        'device_codes' => 'require|array|checkDeviceCodes',
        'search_mode'  => 'require|in:keyword,account',
        'keywords'     => 'require|array|checkKeywords',
        'add_rule'     => 'checkAddRule',
        'scan_config'  => 'checkScanConfig',
        'add_type'     => 'in:0,1',
        'per_kw'       => 'checkPerKw',
        'day_cap'      => 'checkDayCap',
        'minutes'      => 'checkMinutes',
        'time_config'  => 'checkTimeConfig',
    ];

    protected $message = [
        'id.require'            => '请输入任务ID',
        'name.require'          => '请输入任务名称',
        'device_codes.require'  => '请选择设备',
        'device_codes.array'    => '设备参数必须是数组',
        'search_mode.require'   => '请选择搜寻模式',
        'search_mode.in'        => '搜寻模式仅支持关键词或指定账号',
        'keywords.require'      => '请输入线索词或账号名单',
        'keywords.array'        => '线索词或账号名单必须是数组',
        'add_type.in'           => '自动加好友类型不正确',
    ];

    public function sceneAdd()
    {
        return $this->only([
            'name',
            'device_codes',
            'search_mode',
            'keywords',
            'add_rule',
            'scan_config',
            'add_type',
            'per_kw',
            'day_cap',
            'minutes',
            'time_config',
        ]);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    public function scenePause()
    {
        return $this->only(['id']);
    }

    public function sceneRecovery()
    {
        return $this->only(['id']);
    }

    public function sceneSend()
    {
        return $this->only(['id']);
    }

    public function sceneKeywords()
    {
        return $this->only(['id']);
    }

    protected function checkDeviceCodes($value)
    {
        if (!is_array($value) || $value === []) {
            return '请选择设备';
        }
        if (count($value) > 20) {
            return '单次最多选择20台设备';
        }
        return true;
    }

    protected function checkKeywords($value)
    {
        if (!is_array($value) || $value === []) {
            return '请输入线索词或账号名单';
        }
        if (count($value) > 200) {
            return '线索词或账号名单最多200条';
        }
        return true;
    }

    protected function checkAddRule($value)
    {
        if ($value === '' || $value === null) {
            return true;
        }
        if (!in_array((string)$value, ['all', 'phone', 'wechat', 'phone_first'], true)) {
            return '加好友规则仅支持全部、仅手机、仅微信或手机优先';
        }
        return true;
    }

    protected function checkScanConfig($value, $rule, $data = [])
    {
        try {
            DySearchAccountNormalizer::buildScanConfig($value, (string)($data['search_mode'] ?? 'keyword'));
            return true;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    protected function checkPerKw($value)
    {
        if ($value === '' || $value === null) {
            return true;
        }
        try {
            DySearchAccountNormalizer::parseCap($value, '每词上限');
            return true;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    protected function checkDayCap($value)
    {
        if ($value === '' || $value === null) {
            return true;
        }
        try {
            DySearchAccountNormalizer::parseCap($value, '单日上限');
            return true;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    protected function checkMinutes($value, $rule, $data = [])
    {
        if ((int)($data['task_exec_type'] ?? 0) !== 1) {
            return true;
        }
        $minutes = (int)$value;
        if ($minutes < 5 || $minutes > 120) {
            return '即时执行时长须在5到120分钟之间';
        }
        return true;
    }

    protected function checkTimeConfig($value, $rule, $data = [])
    {
        if ((int)($data['task_exec_type'] ?? 0) === 1) {
            return true;
        }
        if ($value === '' || $value === null || (is_array($value) && $value === [])) {
            return '请配置任务执行时间区间';
        }
        return true;
    }
}
