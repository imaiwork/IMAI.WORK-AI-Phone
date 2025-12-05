<?php

namespace app\adminapi\controller\channel;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\channel\OfficialAccountMenuLogic;

/**
 * 微信公众号菜单控制器
 * Class OfficialAccountMenuController
 * @package app\adminapi\controller\channel
 */
class OfficialAccountMenuController extends BaseAdminController
{

    /**
     * @notes 保存菜单
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/3/29 10:41
     */
    public function save()
    {
        $params = $this->request->post();
        $result = OfficialAccountMenuLogic::save($params);
        if (false === $result) {
            return $this->fail(OfficialAccountMenuLogic::getError());
        }
        return $this->success('保存成功', [], 1, 1);
    }


    /**
     * @notes 保存发布菜单
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/3/29 10:42
     */
    public function saveAndPublish()
    {
        $params = $this->request->post();
        $result = OfficialAccountMenuLogic::saveAndPublish($params);
        if ($result) {
            return $this->success('保存并发布成功', [], 1, 1);
        }
        return $this->fail(OfficialAccountMenuLogic::getError());
    }



    /**
     * @notes 查看菜单详情
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/3/29 10:42
     */
    public function detail()
    {
        $result = OfficialAccountMenuLogic::detail();
        return $this->data($result);
    }
}
