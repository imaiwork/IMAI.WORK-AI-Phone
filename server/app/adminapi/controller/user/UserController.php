<?php

namespace app\adminapi\controller\user;

use app\adminapi\controller\BaseAdminController;
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

    public function import(){
        try {
            $file = $this->request->file('file');
            $res = UserLogic::import($file);
            $data['import'] = $res;
            if ($res){
                return $this->success('导入成功',$data,1,0);
            }
            $this->fail('导入失败',$data,0,0);
        } catch (\Exception $e) {
            return $this->fail('导入失败: ' . $e->getMessage(),['import'=>false],0,0);
        }
    }
}
