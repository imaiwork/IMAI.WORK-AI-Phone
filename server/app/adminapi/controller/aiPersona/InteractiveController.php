<?php


namespace app\adminapi\controller\aiPersona;

use app\adminapi\logic\aiPersona\InteractiveLogic;
use app\adminapi\validate\aiPersona\InteractiveValidate;
use app\adminapi\controller\BaseAdminController;
use think\response\Json;

/**
 * InteractiveController
 * @desc 自动互动管家任务
 * @author Qasim
 */
class InteractiveController extends BaseAdminController
{

    public array $notNeedLogin = ['cron'];


    public function detail(): Json
    {
        $getData = (new InteractiveValidate())->get()->goCheck('detail');
        $result = InteractiveLogic::detail($getData);
        return $result ? $this->data($result) : $this->fail(InteractiveLogic::getError());
    }


    public function update()
    {
        $postData = (new InteractiveValidate())->post()->goCheck('edit');
        $edit      = InteractiveLogic::edit($postData);
        return $edit ? $this->success() : $this->fail(InteractiveLogic::getError());
    }
}
