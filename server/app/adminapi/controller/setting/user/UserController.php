<?php

namespace app\adminapi\controller\setting\user;

use app\adminapi\{
    controller\BaseAdminController,
    logic\setting\user\UserLogic,
    validate\setting\UserConfigValidate
};


/**
 * 设置-用户设置控制器
 * Class UserController
 * @package app\adminapi\controller\config
 */
class UserController extends BaseAdminController
{

    /**
     * @notes 获取用户设置
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/3/29 10:08
     */
    public function getConfig()
    {
        $result = (new UserLogic())->getConfig();
        return $this->data($result);
    }


    /**
     * @notes 设置用户设置
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/3/29 10:08
     */
    public function setConfig()
    {
        $params = (new UserConfigValidate())->post()->goCheck('user');
        (new UserLogic())->setConfig($params);
        return $this->success('操作成功', [], 1, 1);
    }


    /**
     * @notes 获取注册配置
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/3/29 10:08
     */
    public function getRegisterConfig()
    {
        $result = (new UserLogic())->getRegisterConfig();
        return $this->data($result);
    }


    /**
     * @notes 设置注册配置
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/3/29 10:08
     */
    public function setRegisterConfig()
    {
        $params = (new UserConfigValidate())->post()->goCheck('register');
        (new UserLogic())->setRegisterConfig($params);
        return $this->success('操作成功', [], 1, 1);
    }
}
