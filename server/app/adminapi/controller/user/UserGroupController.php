<?php

namespace app\adminapi\controller\user;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\user\UserLists;
use app\adminapi\logic\user\UserLogic;
use app\adminapi\validate\user\AdjustUserMoney;
use app\adminapi\validate\user\AdjustUserToken;
use app\adminapi\validate\user\UserValidate;

/**
 * 用户控制器
 * Class UserController
 * @package app\adminapi\controller\user
 */
class UserGroupController extends BaseAdminController
{

    /**
     * @notes 用户列表
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/22 16:16
     */
    public function lists(): \think\response\Json
    {
        return $this->success('Success');
    }
}
