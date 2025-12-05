<?php

namespace app\adminapi\controller\hd;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\hd\HdImageLists;
use app\adminapi\logic\hd\HdImageLogic;



/**
 * Ai绘图
 * Class HdImageController
 * @package app\adminapi\controller\hd
 */
class HdImageController extends BaseAdminController
{
    /**
     * @desc 获取列表
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function lists()
    {
        return $this->dataLists(new HdImageLists());
    }

    /**
     * @notes 删除图片
     * @return Json
     * @author 段誉
     * @date 2022/9/20 17:09
     */
    public function delete()
    {
        $params = $this->request->post();
        return HdImageLogic::delete($params) ? $this->success() : $this->fail(HdImageLogic::getError());
    }

    /**
     * @desc 获取详情
     * @return \think\response\Json
     * @date 2024/5/23 11:36
     * @author dagouzi
     */
    public function detail()
    {
        $params = $this->request->get();
        $result = HdImageLogic::detail($params);
        return $this->data($result);
    }
}
