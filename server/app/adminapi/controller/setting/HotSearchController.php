<?php


namespace app\adminapi\controller\setting;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\setting\HotSearchLogic;

/**
 * 热门搜索设置
 * Class HotSearchController
 * @package app\adminapi\controller\setting
 */
class HotSearchController extends BaseAdminController
{

    /**
     * @notes 获取热门搜索
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/5 19:00
     */
    public function getConfig()
    {
        $result = HotSearchLogic::getConfig();
        return $this->data($result);
    }


    /**
     * @notes 设置热门搜索
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/9/5 19:00
     */
    public function setConfig()
    {
        $params = $this->request->post();
        $result = HotSearchLogic::setConfig($params);
        if (false === $result) {
            return $this->fail(HotSearchLogic::getError() ?: '系统错误');
        }
        return $this->success('设置成功', [], 1, 1);
    }
}
