<?php

namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use app\api\lists\device\DySearchRecordLists;
use app\api\lists\device\DySearchTaskLists;
use app\api\logic\device\DySearchTaskLogic;
use app\api\validate\device\DySearchTaskValidate;
use think\exception\HttpResponseException;

/**
 * 抖音获客
 */
class DySearchTaskController extends BaseApiController
{
    public function lists()
    {
        return $this->dataLists(new DySearchTaskLists());
    }

    public function add()
    {
        try {
            $params = (new DySearchTaskValidate())->post()->goCheck('add');
            $result = DySearchTaskLogic::add($params);
            if ($result) {
                return $this->data(DySearchTaskLogic::getReturnData());
            }
            return $this->fail(DySearchTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new DySearchTaskValidate())->get()->goCheck('detail');
            $result = DySearchTaskLogic::detail($params);
            if ($result) {
                return $this->data(DySearchTaskLogic::getReturnData());
            }
            return $this->fail(DySearchTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new DySearchTaskValidate())->post()->goCheck('delete');
            $result = DySearchTaskLogic::delete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(DySearchTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function pause()
    {
        try {
            $params = (new DySearchTaskValidate())->post()->goCheck('pause');
            $result = DySearchTaskLogic::pause($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(DySearchTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function recovery()
    {
        try {
            $params = (new DySearchTaskValidate())->post()->goCheck('recovery');
            $result = DySearchTaskLogic::recovery($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(DySearchTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function send()
    {
        try {
            $params = (new DySearchTaskValidate())->post()->goCheck('send');
            $result = DySearchTaskLogic::send($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(DySearchTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function records()
    {
        return $this->dataLists(new DySearchRecordLists());
    }

    public function history()
    {
        $result = DySearchTaskLogic::history();
        if ($result) {
            return $this->data(DySearchTaskLogic::getReturnData());
        }
        return $this->fail(DySearchTaskLogic::getError());
    }

    public function keywords()
    {
        try {
            $params = (new DySearchTaskValidate())->get()->goCheck('keywords');
            $result = DySearchTaskLogic::keywordsCount($params);
            if ($result) {
                return $this->data(DySearchTaskLogic::getReturnData());
            }
            return $this->fail(DySearchTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
