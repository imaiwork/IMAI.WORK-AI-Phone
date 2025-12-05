<?php


namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\logic\sv\SvAccountLogic;
use app\api\validate\sv\SvAccountValidate;
use app\api\lists\sv\SvAccountLists;
use app\api\lists\sv\AllAccountLists;

use think\exception\HttpResponseException;

/**
 * SvController
 * @author Qasim
 */
class AccountController extends BaseApiController
{

    public array $notNeedLogin = [];

    /**
     * @desc 列表
     */
    public function lists()
    {
        return $this->dataLists(new SvAccountLists());
    }

    public function allLists()
    {
        return $this->dataLists(new AllAccountLists());
    }

    /**
     * @desc 平台
     */
    public function add()
    {
        try {
            $params = (new SvAccountValidate())->post()->goCheck('add');
            $result = SvAccountLogic::addSvAccount($params);
            if ($result) {
                return $this->success(data: SvAccountLogic::getReturnData());
            }
            return $this->fail(SvAccountLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 平台
     */
    public function update()
    {
        try {
            $params = (new SvAccountValidate())->post()->goCheck('update');
            $result = SvAccountLogic::updateSvAccount($params);
            if ($result) {
                return $this->success(data: SvAccountLogic::getReturnData());
            }
            return $this->fail(SvAccountLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 平台
     */
    public function ai()
    {
        try {
            $params = (new SvAccountValidate())->post()->goCheck('ai');
            $result = SvAccountLogic::updateSvAccountAi($params);
            if ($result) {
                return $this->success(data: SvAccountLogic::getReturnData());
            }
            return $this->fail(SvAccountLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 下线
     */
    public function offline()
    {
        try {
            $params = (new SvAccountValidate())->post()->goCheck('offline');
            $result = SvAccountLogic::offlineSvAccount($params);
            if ($result) {
                return $this->success(data: SvAccountLogic::getReturnData());
            }
            return $this->fail(SvAccountLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }



    /**
     * @desc 获取详情
     */
    public function detail()
    {
        try {
            $params = (new SvAccountValidate())->get()->goCheck('detail');
            $result = SvAccountLogic::detailSvAccount($params);
            if ($result) {
                return $this->data(SvAccountLogic::getReturnData());
            }
            return $this->fail(SvAccountLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 删除
     */
    public function delete()
    {
        try {
            $params = (new SvAccountValidate())->post()->goCheck('delete');
            $result = SvAccountLogic::deleteSvAccount($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvAccountLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
