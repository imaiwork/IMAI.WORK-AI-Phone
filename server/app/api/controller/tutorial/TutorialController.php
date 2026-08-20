<?php

namespace app\api\controller\tutorial;

use app\api\controller\BaseApiController;
use app\api\lists\tutorial\TutorialLists;
use app\api\logic\tutorial\TutorialLogic;
use app\api\validate\tutorial\TutorialValidate;
use think\exception\HttpResponseException;

class TutorialController extends BaseApiController
{
    public array $notNeedLogin = ['lists', 'detail'];

    public function lists()
    {
        return $this->dataLists(new TutorialLists());
    }

    public function detail()
    {
        try {
            $params = (new TutorialValidate())->get()->goCheck('detail');
            $result = TutorialLogic::get($params);
            if ($result) {
                return $this->data(TutorialLogic::getReturnData());
            }
            return $this->fail(TutorialLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
