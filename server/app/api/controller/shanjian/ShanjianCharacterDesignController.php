<?php

namespace app\api\controller\shanjian;

use app\api\controller\BaseApiController;
use app\api\lists\shanjian\ShanjianCharacterDesignLists;
use app\api\logic\shanjian\ShanjianCharacterDesignLogic;
use app\api\validate\shanjian\ShanjianCharacterDesignValidate;
use think\exception\HttpResponseException;

class ShanjianCharacterDesignController extends BaseApiController
{
    public function add()
    {
        try {
            $params = (new ShanjianCharacterDesignValidate())->post()->goCheck('add');
            $result = ShanjianCharacterDesignLogic::add($params);
            if ($result) {
                return $this->success('添加成功');
            }
            return $this->fail(ShanjianCharacterDesignLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new ShanjianCharacterDesignValidate())->post()->goCheck('update');
            $result = ShanjianCharacterDesignLogic::update($params);
            if ($result) {
                return $this->success(data: ShanjianCharacterDesignLogic::getReturnData());
            }
            return $this->fail(ShanjianCharacterDesignLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = $this->request->post();
            $result = ShanjianCharacterDesignLogic::delete($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(ShanjianCharacterDesignLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new ShanjianCharacterDesignValidate())->get()->goCheck('detail');
            $result = ShanjianCharacterDesignLogic::detail($params);
            if ($result) {
                return $this->data(ShanjianCharacterDesignLogic::getReturnData());
            }
            return $this->fail(ShanjianCharacterDesignLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function lists()
    {
        return $this->dataLists(new ShanjianCharacterDesignLists());
    }
}


