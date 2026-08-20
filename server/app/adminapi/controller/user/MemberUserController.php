<?php

namespace app\adminapi\controller\user;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\user\MemberUserLists;
use app\adminapi\logic\user\MemberUserLogic;

/**
 * 会员订阅用户管理
 * 路由: user.member_user/*
 */
class MemberUserController extends BaseAdminController
{
    public function lists()
    {
        return $this->dataLists(new MemberUserLists());
    }

    public function grant()
    {
        $params = $this->request->post();
        if (!MemberUserLogic::grant($params)) {
            return $this->fail(MemberUserLogic::getError());
        }
        return $this->success('已开通', [], 1, 1);
    }

    public function cancel()
    {
        $userId = (int)$this->request->post('user_id', 0);
        if (!$userId) {
            return $this->fail('缺少 user_id');
        }
        if (!MemberUserLogic::cancel($userId)) {
            return $this->fail(MemberUserLogic::getError());
        }
        return $this->success('已取消', [], 1, 1);
    }
}
