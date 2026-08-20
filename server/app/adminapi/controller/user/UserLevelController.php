<?php

namespace app\adminapi\controller\user;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\user\UserLevelLists;
use app\adminapi\logic\user\UserLevelLogic;
use app\adminapi\validate\user\UserLevelValidate;

/**
 * 用户等级控制器
 * Class UserLevelController
 * @package app\adminapi\controller\user
 */
class UserLevelController extends BaseAdminController
{
    /**
     * @notes 用户等级列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new UserLevelLists());
    }

    /**
     * @notes 获取用户等级详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new UserLevelValidate())->goCheck('detail');
        $detail = UserLevelLogic::formatDetail(UserLevelLogic::detail($params['id']));
        return $this->success('', $detail);
    }

    /**
     * @notes 新增用户等级
     * @return \think\response\Json
     */
    public function add()
    {
        $params = (new UserLevelValidate())->post()->goCheck('create');
        $params = UserLevelLogic::normalizeParams($params);
        $result = UserLevelLogic::add($params);
        if ($result === false) {
            return $this->fail(UserLevelLogic::getError());
        }
        return $this->success('新增成功', [], 1, 1);
    }

    /**
     * @notes 编辑用户等级
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = (new UserLevelValidate())->post()->goCheck('edit');
        $params = UserLevelLogic::normalizeParams($params);
        $result = UserLevelLogic::edit($params);
        if ($result === false) {
            return $this->fail(UserLevelLogic::getError());
        }
        return $this->success('编辑成功', [], 1, 1);
    }

    /**
     * @notes 删除用户等级
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new UserLevelValidate())->post()->goCheck('delete');
        $result = UserLevelLogic::delete($params['id']);
        if ($result === false) {
            return $this->fail(UserLevelLogic::getError());
        }
        return $this->success('删除成功', [], 1, 1);
    }
}
