<?php

namespace app\api\controller\tutorial;

use app\api\controller\BaseApiController;
use app\api\lists\tutorial\TutorialCategoryLists;
use app\api\logic\tutorial\TutorialCategoryLogic;
use app\api\validate\tutorial\TutorialCategoryValidate;
use think\exception\HttpResponseException;

class TutorialCategoryController extends BaseApiController
{
    public array $notNeedLogin = ['lists', 'detail'];

    public function lists()
    {
        return $this->dataLists(new TutorialCategoryLists());
    }

    public function detail()
    {
        try {
            $params = (new TutorialCategoryValidate())->get()->goCheck('detail');
            $result = TutorialCategoryLogic::get($params);
            if ($result) {
                return $this->data(TutorialCategoryLogic::getReturnData());
            }
            return $this->fail(TutorialCategoryLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
