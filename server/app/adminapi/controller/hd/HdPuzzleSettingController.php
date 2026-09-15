<?php

namespace app\adminapi\controller\hd;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\hd\HdPuzzleSettingLogic;
use app\adminapi\lists\hd\HdPuzzleSettingLists;
use app\adminapi\validate\hd\HdPuzzleSettingValidate;
use think\response\Json;

class HdPuzzleSettingController extends BaseAdminController
{
    /**
     * @notes 拼图设置列表
     * @return Json
     */
    public function lists(): Json
    {
        return $this->dataLists(new HdPuzzleSettingLists());
    }

    /**
     * @notes 删除拼图设置
     * @return Json
     */
    public function delete(): Json
    {
        $params = $this->request->post();
        $result = HdPuzzleSettingLogic::delete($params);
        if ($result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(HdPuzzleSettingLogic::getError());
    }
    

}