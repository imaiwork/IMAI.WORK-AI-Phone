<?php


namespace app\adminapi\controller\aiPersona;

use app\adminapi\logic\aiPersona\ClueTouchLogic;
use app\adminapi\validate\aiPersona\ClueTouchValidate;
use app\adminapi\controller\BaseAdminController;
use think\response\Json;

/**
 * ClueTouchController
 * @desc IP人设触达自动任务
 * @author Qasim
 */
class ClueTouchController extends BaseAdminController
{



    public function detail(): Json
    {
        $getData = (new ClueTouchValidate())->get()->goCheck('detail');
        $result = ClueTouchLogic::detail($getData);
        return $result ? $this->data($result) : $this->fail(ClueTouchLogic::getError());
    }


    public function update()
    {
        $postData = (new ClueTouchValidate())->post()->goCheck('edit');
        $edit      = ClueTouchLogic::edit($postData);
        return $edit ? $this->success() : $this->fail(ClueTouchLogic::getError());
    }
}
