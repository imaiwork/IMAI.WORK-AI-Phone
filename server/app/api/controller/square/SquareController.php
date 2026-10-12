<?php

namespace app\api\controller\square;

use app\api\controller\BaseApiController;
use app\api\logic\square\SquareLogic;
use app\api\validate\square\SquareValidate;

class SquareController extends BaseApiController
{
    public function lists()
    {
        $params = (new SquareValidate())->get()->goCheck('browse');
        return $this->data(SquareLogic::browse($params));
    }

    public function refs()
    {
        $params = (new SquareValidate())->get()->goCheck('refs');
        if (!SquareLogic::refs($params)) {
            return $this->fail(SquareLogic::getError());
        }
        return $this->data(SquareLogic::getReturnData());
    }

    public function addRefs()
    {
        $params = (new SquareValidate())->post()->goCheck('mutate');
        if (!SquareLogic::addRefs($params)) {
            return $this->fail(SquareLogic::getError());
        }
        $data = SquareLogic::getReturnData();
        return $this->success($data['message'] ?? '已加入', $data);
    }

    public function removeRefs()
    {
        $params = (new SquareValidate())->post()->goCheck('mutate');
        if (!SquareLogic::removeRefs($params)) {
            return $this->fail(SquareLogic::getError());
        }
        return $this->success('已从我的素材移除', SquareLogic::getReturnData());
    }
}