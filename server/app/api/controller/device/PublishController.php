<?php


namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\validate\device\PublishValidate;
use app\api\validate\device\PublishDetailValidate;
use app\api\logic\device\PublishLogic;
use app\api\lists\device\PublishLists;
use app\api\lists\device\PublishDetailLists;
use app\api\lists\device\PublishAccountLists;

/**
 * PublishController
 * @desc 发布设置
 * @author Qasim
 */
class PublishController extends BaseApiController
{

    public array $notNeedLogin = ['setPublishDetail'];

    /**
     * @desc 获取列表
     */
    public function lists()
    {
        return $this->dataLists(new PublishLists());
    }

    public function accountLists()
    {
        return $this->dataLists(new PublishAccountLists());
    }

    public function accountDetail()
    {
        try {
            $params = (new PublishValidate())->get()->goCheck('detail');
            $result = PublishLogic::accountDetail($params);
            if ($result) {
                return $this->data(PublishLogic::getReturnData());
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function accountDelete()
    {
        try {
            $params = $this->request->post();
            $result = PublishLogic::accountDelete($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function  accountUpdate(){
         try {
            $params = $this->request->post();
            $result = PublishLogic::accountUpdate($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 添加发布设置
     */
    public function add()
    {
        try {
            $params = (new PublishValidate())->post()->goCheck('add');
            $result = PublishLogic::add($params);
            if ($result) {
                return $this->success(data: PublishLogic::getReturnData());
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 更新机器人
     */
    public function update()
    {
        try {
            $params = (new PublishValidate())->post()->goCheck('update');
            $result = PublishLogic::update($params);
            if ($result) {
                return $this->success(data: PublishLogic::getReturnData());
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function change()
    {
        try {
            $params = (new PublishValidate())->post()->goCheck('change');
            $result = PublishLogic::change($params);
            if ($result) {
                return $this->success(data: PublishLogic::getReturnData());
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }



    /**
     * @desc 删除机器人
     */
    public function delete()
    {
        try {
            $params = $this->request->post();
            $result = PublishLogic::delete($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 详情
     */
    public function detail()
    {
        try {
            $params = (new PublishValidate())->get()->goCheck('detail');
            $result = PublishLogic::detail($params);
            if ($result) {
                return $this->data(PublishLogic::getReturnData());
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function recordLists(){

        return $this->dataLists(new PublishDetailLists());
    }

    public function recordDetail(){

        try {
            $params = (new PublishDetailValidate())->get()->goCheck('detail');
            $result = PublishLogic::recordDetail($params);
            if ($result) {
                return $this->data(PublishLogic::getReturnData());
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function recordDelete()
    {
        try {
            $params = $this->request->post();
            $result = PublishLogic::recordDelete($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function recordRetry(){

        try {
            $params = (new PublishDetailValidate())->post()->goCheck('retry');
            $result = PublishLogic::recordRetry($params);
            if ($result) {
                return $this->data(PublishLogic::getReturnData());
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function recordRepublish(){
        try {
            $params = (new PublishDetailValidate())->post()->goCheck('republish');
            $result = PublishLogic::recordRepublish($params);
            if ($result) {
                return $this->data(PublishLogic::getReturnData());
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function testAdd(){
        try {
            $params = (new PublishDetailValidate())->post()->goCheck('test');
            $result = PublishLogic::testAdd($params);
            if ($result) {
                return $this->success(data: PublishLogic::getReturnData());
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function testPublish(){
        try {
            $params = $this->request->post();
            $result = PublishLogic::testPublish($params);
            if ($result) {
                return $this->success(data: PublishLogic::getReturnData());
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    
    public function setPublishDetail(){

        PublishLogic::setPublishDetail();
    }
}

