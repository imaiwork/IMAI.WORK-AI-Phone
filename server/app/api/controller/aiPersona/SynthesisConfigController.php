<?php

namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use app\api\lists\aiPersona\SynthesisConfigLists;
use app\api\logic\aiPersona\SynthesisConfigLogic;
use app\api\validate\aiPersona\SynthesisConfigValidate;
use think\exception\HttpResponseException;

class SynthesisConfigController extends BaseApiController
{
    public function add()
    {
        try {
            $params = (new SynthesisConfigValidate())->post()->goCheck('add');
            $result = SynthesisConfigLogic::add($params);
            if ($result) {
                return $this->success('添加成功');
            }
            return $this->fail(SynthesisConfigLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new SynthesisConfigValidate())->post()->goCheck('update');
            $result = SynthesisConfigLogic::update($params);
            if ($result) {
                return $this->success(data: SynthesisConfigLogic::getReturnData());
            }
            return $this->fail(SynthesisConfigLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new SynthesisConfigValidate())->post()->goCheck('delete');
            $result = SynthesisConfigLogic::delete($params['ids']);
            if ($result) {
                return $this->success('删除成功');
            }
            return $this->fail(SynthesisConfigLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new SynthesisConfigValidate())->get()->goCheck('detail');
            $result = SynthesisConfigLogic::detail($params);
            if ($result) {
                return $this->data(SynthesisConfigLogic::getReturnData());
            }
            return $this->fail(SynthesisConfigLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function lists()
    {
        return $this->dataLists(new SynthesisConfigLists());
    }

    public function getByPersonaId()
    {
        try {
            $params = $this->request->get();
            $result = SynthesisConfigLogic::getByPersonaId($params);
            if ($result) {
                return $this->data(SynthesisConfigLogic::getReturnData());
            }
            return $this->fail(SynthesisConfigLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
