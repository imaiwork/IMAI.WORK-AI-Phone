<?php

namespace app\adminapi\controller\auth;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\auth\MenuLists;
use app\adminapi\logic\auth\MenuLogic;
use app\adminapi\validate\auth\MenuValidate;


/**
 * 系统菜单权限
 * Class MenuController
 * @package app\adminapi\controller\setting\system
 */
class MenuController extends BaseAdminController
{

    /**
     * @notes 获取菜单路由
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/6/29 17:41
     */
    public function route()
    {
        $result = MenuLogic::getMenuByAdminId($this->adminId);
        return $this->data($result);
    }


    /**
     * @notes 获取菜单列表
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/6/29 17:23
     */
    public function lists()
    {
        return $this->dataLists(new MenuLists());
    }


    /**
     * @notes 菜单详情
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/6/30 10:07
     */
    public function detail()
    {
        $params = (new MenuValidate())->goCheck('detail');
        return $this->data(MenuLogic::detail($params));
    }


    /**
     * @notes 添加菜单
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/6/30 10:07
     */
    public function add()
    {
        $params = (new MenuValidate())->post()->goCheck('add');
        MenuLogic::add($params);
        return $this->success('操作成功', [], 1, 1);
    }


    /**
     * @notes 编辑菜单
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/6/30 10:07
     */
    public function edit()
    {
        $params = (new MenuValidate())->post()->goCheck('edit');
        MenuLogic::edit($params);
        return $this->success('操作成功', [], 1, 1);
    }


    /**
     * @notes 删除菜单
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/6/30 10:07
     */
    public function delete()
    {
        $params = (new MenuValidate())->post()->goCheck('delete');
        MenuLogic::delete($params);
        return $this->success('操作成功', [], 1, 1);
    }


    /**
     * @notes 更新状态
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/7/6 17:04
     */
    public function updateStatus()
    {
        $params = (new MenuValidate())->post()->goCheck('status');
        MenuLogic::updateStatus($params);
        return $this->success('操作成功', [], 1, 1);
    }


    /**
     * @notes 获取菜单数据
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/10/13 11:03
     */
    public function all()
    {
        $result = MenuLogic::getAllData();
        return $this->data($result);
    }
}
