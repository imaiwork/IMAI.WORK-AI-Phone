<?php

namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use app\api\lists\aiPersona\CopywritingLibraryLists;
use app\api\logic\aiPersona\CopywritingLibraryLogic;
use app\api\validate\aiPersona\CopywritingLibraryValidate;
use think\exception\HttpResponseException;

class CopywritingLibraryController extends BaseApiController
{
    public array $notNeedLogin = [];

    public function lists()
    {
        return $this->dataLists(new CopywritingLibraryLists());
    }

    public function add()
    {
        try {
            $params = (new CopywritingLibraryValidate())->post()->goCheck('add');
            $result = CopywritingLibraryLogic::add($params);
            if ($result) {
                return $this->data(CopywritingLibraryLogic::getReturnData());
            }
            return $this->fail(CopywritingLibraryLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function batchAdd()
    {
        try {
            $params = (new CopywritingLibraryValidate())->post()->goCheck('batchAdd');
            $result = CopywritingLibraryLogic::batchAdd($params);
            if ($result) {
                return $this->data(CopywritingLibraryLogic::getReturnData());
            }
            return $this->fail(CopywritingLibraryLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new CopywritingLibraryValidate())->post()->goCheck('update');
            $result = CopywritingLibraryLogic::update($params);
            if ($result) {
                return $this->data(CopywritingLibraryLogic::getReturnData());
            }
            return $this->fail(CopywritingLibraryLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new CopywritingLibraryValidate())->get()->goCheck('detail');
            $result = CopywritingLibraryLogic::detail($params);
            if ($result) {
                return $this->data(CopywritingLibraryLogic::getReturnData());
            }
            return $this->fail(CopywritingLibraryLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new CopywritingLibraryValidate())->post()->goCheck('delete');
            $result = CopywritingLibraryLogic::del($params['ids']);
            if ($result) {
                return $this->success('删除成功', CopywritingLibraryLogic::getReturnData());
            }
            return $this->fail(CopywritingLibraryLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function import()
    {
        try {
            $params = (new CopywritingLibraryValidate())->post()->goCheck('import');
            $result = CopywritingLibraryLogic::import($params, $params['file']);
            if ($result) {
                return $this->data(CopywritingLibraryLogic::getReturnData());
            }
            return $this->fail(CopywritingLibraryLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
