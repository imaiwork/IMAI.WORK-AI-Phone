<?php

namespace app\adminapi\controller\decorate;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\decorate\DecorateDataLogic;
use think\response\Json;

/**
 * 装修-数据
 * Class DataController
 * @package app\adminapi\controller\decorate
 */
class DataController extends BaseAdminController
{
    /**
     * @notes 文章列表
     * @return Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mjf
     * @date 2024/3/14 18:13
     */
    public function article(): Json
    {
        $limit = $this->request->get('limit/d', 10);
        $result = DecorateDataLogic::getArticleLists($limit);
        return $this->data($result);
    }

    /**
     * @notes pc设置
     * @return Json
     * @author mjf
     * @date 2024/3/14 18:13
     */
    public function pc(): Json
    {
        $result = DecorateDataLogic::pc();
        return $this->data($result);
    }
}
