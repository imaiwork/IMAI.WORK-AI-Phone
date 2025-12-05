<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 账号校验
 * Class SvAccountValidate
 * @package app\api\validate\sv
 * @author Qasim
 */
class SvAccountValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'device_code' => 'require',
        'account' => 'require',
        'open_ai' => 'in:0,1',
        'type' => 'require|in:3',
        "takeover_mode" => 'in:0,1',
        "takeover_type" => 'in:0,1,2',
        "sort" => 'number',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'device_code.require' => '请输入设备码',
        'account.require' => '请输入账号ID',
        'nickname.require' => '请输入账号昵称',
        'avatar.require' => '请输入账号头像',
        'status.require' => '请输入账号状态',
        "takeover_mode.in" => '接管模式值只能是0或1',
        "takeover_type.in" => '接管类型值只能是0,1,2',
        "sort.number" => '排序值必须是整数',
        "open_ai.in" => '是否开启AI功能值只能是0或1',
        "type.require" => '接管类型值只能是0,1,2',
        "type.in" => '请输入账号类型',
    ];


    /**
     * @notes 添加账号
     * @return SvAccountValidate
     */
    public function sceneAdd()
    {
        return $this->only(['device_code', 'account']);
    }

    /**
     * @notes 更新账号
     * @return SvAccountValidate
     */
    public function sceneUpdate()
    {
        return $this->only(['id', 'device_code', 'account']);
    }

    /**
     * @notes AI
     * @return SvAccountValidate
     */
    public function sceneAi()
    {
        return $this->only(['account', 'remark', 'takeover_mode', 'takeover_type', 'sort', 'open_ai']);
    }

    /**
     * @notes 详情
     * @return SvAccountValidate
     */
    public function sceneDetail()
    {
        return $this->only(['account']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}
