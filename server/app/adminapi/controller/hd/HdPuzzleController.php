<?php

namespace app\adminapi\controller\hd;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\hd\HdPuzzleLogic;
use app\adminapi\lists\hd\HdPuzzleLists;
use app\adminapi\validate\hd\HdPuzzleValidate;
use think\response\Json;

class HdPuzzleController extends BaseAdminController
{
    /**
     * @notes 拼图任务列表
     * @return Json
     */
    public function lists(): Json
    {
        return $this->dataLists(new HdPuzzleLists());
    }

    
    /**
     * @notes 删除拼图任务
     * @return Json
     */
    public function delete(): Json
    {
        $params = $this->request->post();
        $result = HdPuzzleLogic::delete($params);
        if ($result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(HdPuzzleLogic::getError());
    }

}