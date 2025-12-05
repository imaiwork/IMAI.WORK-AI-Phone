<?php

namespace app\adminapi\controller\decorate;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\decorate\DecorateTabbarLogic;

/**
 * 装修-底部导航
 * Class DecorateTabbarController
 * @package app\adminapi\controller\decorate
 */
class TabbarController extends BaseAdminController
{

    /**
     * @notes 底部导航详情
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2022/9/7 16:39
     */
    public function detail()
    {
        $data = DecorateTabbarLogic::detail();
        return $this->data($data);
    }


    /**
     * @notes 底部导航保存
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/6 9:58
     */
    public function save()
    {
        $params = $this->request->post();
        DecorateTabbarLogic::save($params);
        return $this->success('操作成功', [], 1, 1);
    }
}
