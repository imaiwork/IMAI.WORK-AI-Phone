<?php

namespace app\api\validate\wechat;

use app\common\validate\BaseValidate;

/**
 * 微信机器人校验
 * Class RobotValidate
 * @package app\api\validate\wechat
 * @author Qasim
 */
class RobotValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'logo' => 'require',
        'name' => 'require',
        'description' => 'require',
        'company_background' => 'require',
        'question' => 'require',
        'answer' => 'require',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'logo.require' => '请输入logo',
        'name.require' => '请输入名称',
        'description.require' => '请输入描述',
        'company_background.require' => '请输入公司背景',
        'question.require' => '请输入问题',
        'answer.require' => '请输入回答',
    ];


    /**
     * @notes 添加微信
     * @return WechatValidate
     */
    public function sceneAdd()
    {
        return $this->only(['logo', 'name', 'description', 'company_background']);
    }

    /**
     * @notes 更新微信
     * @return WechatValidate
     */
    public function sceneUpdate()
    {
        return $this->only(['id', 'logo', 'name', 'description', 'company_background']);
    }

    /**
     * @notes 删除
     * @return WechatValidate
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 详情
     * @return WechatValidate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }
}
