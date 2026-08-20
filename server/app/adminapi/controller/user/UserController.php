<?php

namespace app\adminapi\controller\user;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\user\AccountLogLists;
use app\adminapi\lists\user\UserLists;
use app\adminapi\logic\user\UserLogic;
use app\adminapi\validate\user\AdjustUserMoney;
use app\adminapi\validate\user\AdjustUserToken;
use app\adminapi\validate\user\UserValidate;
use think\response\Json;

/**
 * 用户控制器
 * Class UserController
 * @package app\adminapi\controller\user
 */
class UserController extends BaseAdminController
{
    /**
     * @notes 用户列表
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/22 16:16
     */
    public function lists()
    {
        return $this->dataLists(new UserLists());
    }

    public function accountLogLists()
    {
        return $this->dataLists(new AccountLogLists());
    }


    /**
     * @notes 获取用户详情
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/22 16:34
     */
    public function detail()
    {
        $params = (new UserValidate())->goCheck('detail');
        $detail = UserLogic::detail($params['id']);
        return $this->success('', $detail);
    }


    /**
     * @notes 编辑用户信息
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/22 16:34
     */
    public function edit()
    {
        $params = (new UserValidate())->post()->goCheck('setInfo');
        UserLogic::setUserInfo($params);
        return $this->success('操作成功', [], 1, 1);
    }

    /**
     * @notes 拉黑/解除拉黑用户
     * @return Json
     */
    public function blacklist(): Json
    {
        $params = (new UserValidate())->post()->goCheck('detail');
        if ($this->request->has('type')) {
            $params['type'] = (int)$this->request->post('type');
        }
        $result = UserLogic::blacklist($params);
        if ($result === false) {
            return $this->fail(UserLogic::getError());
        }
        return $this->success('操作成功', [], 1, 1);
    }

    /**
     * @notes 变更用户会员等级
     * @return Json
     */
    public function changeLevel(): Json
    {
        $params = (new UserValidate())->post()->goCheck('changeLevel');
        $result = UserLogic::changeLevel($params);
        if ($result === false) {
            return $this->fail(UserLogic::getError());
        }
        return $this->success('操作成功', UserLogic::getReturnData(), 1, 1);
    }

    /**
     * @notes 编辑用户分销信息
     * @return \think\response\Json
     */
    public function setDistribution()
    {
        $params = (new \app\adminapi\validate\user\UserDistributionValidate())->post()->goCheck('setInfo');
        UserLogic::setDistributionInfo($params);
        return $this->success('操作成功', [], 1, 1);
    }


    /**
     * @notes 调整用户余额
     * @return \think\response\Json
     * @author 段誉
     * @date 2023/2/23 14:33
     */
    public function adjustMoney()
    {
        $params = (new AdjustUserMoney())->post()->goCheck();
        $res = UserLogic::adjustUserMoney($params);
        if (true === $res) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail($res);
    }


    /**
     * @notes 调整用户余额
     * @return \think\response\Json
     * @author 段誉
     * @date 2023/2/23 14:33
     */
    public function adjustUserTokens()
    {
        $params = (new AdjustUserToken())->post()->goCheck();
        $res = UserLogic::adjustUserTokens($params);
        if (true === $res) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail($res);
    }

    /**
     * @notes 下级充值业绩清零
     * @return \think\response\Json
     */
    public function resetRechargeStats(): Json
    {
        $params = (new UserValidate())->post()->goCheck('detail');
        $result = UserLogic::resetRechargeStats($params);
        if ($result === false) {
            return $this->fail(UserLogic::getError());
        }
        return $this->success('清零成功', UserLogic::getReturnData());
    }


    /**
     * @notes 编辑用户信息
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/22 16:34
     */
    public function editPas()
    {
        $params = (new UserValidate())->post()->goCheck('detail');
        UserLogic::setUserPas($params);
        return $this->success('操作成功', [], 1, 1);
    }

    public function add()
    {
        $params = (new UserValidate())->post()->goCheck('create');
        $result = UserLogic::createUser($params);
        if ($result === false) {
            return $this->fail(UserLogic::getError());
        }
        return $this->success('创建成功', [], 1, 1);
    }

    public function import()
    {
        try {
            $file = $this->request->file('file');
            $res = UserLogic::import($file);
            $data['import'] = $res;
            if ($res) {
                return $this->success('导入成功', $data, 1, 0);
            }
            $this->fail('导入失败', $data, 0, 0);
        } catch (\Exception $e) {
            return $this->fail('导入失败: ' . $e->getMessage(), ['import' => false], 0, 0);
        }
    }
}
