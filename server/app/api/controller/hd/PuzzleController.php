<?php

namespace app\api\controller\hd;

use app\api\controller\BaseApiController;
use app\api\lists\hd\HdPuzzleLists;
use app\api\logic\hd\HdPuzzleLogic;
use app\api\validate\hd\HdPuzzleValidate;
use think\exception\HttpResponseException;

/**
 * PuzzleController
 */
class PuzzleController extends BaseApiController
{
    public array $notNeedLogin = [];

    public function lists()
    {
        return $this->dataLists(new HdPuzzleLists());
    }

    public function add()
    {
        try {
            $params = (new HdPuzzleValidate())->post()->goCheck('add');
            $result = HdPuzzleLogic::addPuzzle($params);
            if ($result) {
                return $this->data(HdPuzzleLogic::getReturnData());
            }
            return $this->fail(HdPuzzleLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new HdPuzzleValidate())->get()->goCheck('detail');
            $result = HdPuzzleLogic::detailPuzzle($params);
            if ($result) {
                return $this->data(HdPuzzleLogic::getReturnData());
            }
            return $this->fail(HdPuzzleLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new HdPuzzleValidate())->post()->goCheck('delete');
            $result = HdPuzzleLogic::deletePuzzle($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(HdPuzzleLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new HdPuzzleValidate())->post()->goCheck('update');
            $result = HdPuzzleLogic::updatePuzzle($params);
            if ($result) {
                return $this->data(HdPuzzleLogic::getReturnData());
            }
            return $this->fail(HdPuzzleLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}

