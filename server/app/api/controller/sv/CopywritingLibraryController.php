<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\logic\sv\SvCopywritingLibraryLogic;
use app\api\validate\sv\CopywritingLibraryValidate;
use app\api\lists\sv\SvCopywritingLibraryLists;
use think\exception\HttpResponseException;

/**
 * CopywritingLibraryController
 * 文案库控制器
 */
class CopywritingLibraryController extends BaseApiController
{
    public array $notNeedLogin = [];

    public function lists()
    {
        return $this->dataLists(new SvCopywritingLibraryLists());
    }

    public function add()
    {
        try {
            $params = (new CopywritingLibraryValidate())->post()->goCheck('add');
            $result = SvCopywritingLibraryLogic::add($params);
            if ($result) {
                return $this->data(SvCopywritingLibraryLogic::getReturnData());
            }
            return $this->fail(SvCopywritingLibraryLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new CopywritingLibraryValidate())->get()->goCheck('detail');
            $result = SvCopywritingLibraryLogic::detail($params);
            if ($result) {
                return $this->data(SvCopywritingLibraryLogic::getReturnData());
            }
            return $this->fail(SvCopywritingLibraryLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function del()
    {
        try {
            $params = (new CopywritingLibraryValidate())->post()->goCheck('delete');
            $result = SvCopywritingLibraryLogic::del($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvCopywritingLibraryLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new CopywritingLibraryValidate())->post()->goCheck('update');
            $result = SvCopywritingLibraryLogic::update($params);
            if ($result) {
                return $this->data(SvCopywritingLibraryLogic::getReturnData());
            }
            return $this->fail(SvCopywritingLibraryLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function addAi()
    {
        try {
            $params = (new CopywritingLibraryValidate())->post()->goCheck('addAi');
            $result = SvCopywritingLibraryLogic::addAi($params);
            if ($result) {
                return $this->data(SvCopywritingLibraryLogic::getReturnData());
            }
            return $this->fail(SvCopywritingLibraryLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

} 