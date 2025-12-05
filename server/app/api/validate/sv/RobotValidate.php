<?php

namespace app\api\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 机器人校验
 * Class RobotValidate
 * @package app\api\validate\sv
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
        'profile' => 'require',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'logo.require' => '请输入logo',
        'name.require' => '请输入名称',
        'description.require' => '请输入描述',
        'company_background.require' => '请输入公司背景',
        'profile.require' => '请输入简介',
    ];


    /**
     * @notes 添加
     * @return Validate
     */
    public function sceneAdd()
    {
        return $this->only(['logo', 'name', 'description', 'company_background', 'profile']);
    }

    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['id', 'logo', 'name', 'description', 'company_background', 'profile']);
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
}
