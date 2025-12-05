<?php


namespace app\adminapi\controller\kb;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\kb\KbTeachLists;
use app\adminapi\logic\kb\KbTeachLogic;
use think\response\Json;

/**
 * 训练数据管理
 */
class TeachController extends BaseAdminController
{
    /**
     * @notes 训练数据列表
     * @return Json
     * @author kb
     */
    public function lists(): Json
    {
        return $this->dataLists(new KbTeachLists());
    }

    /**
     * @notes 训练数据删除
     * @return Json
     * @author kb
     */
    public function del(): Json
    {
        $uuid = $this->request->post('uuid');
        $result = KbTeachLogic::del($uuid);
        if ($result === false) {
            return $this->fail(KbTeachLogic::getError());
        }
        return $this->success('删除成功', [], 1, 1);
    }
}