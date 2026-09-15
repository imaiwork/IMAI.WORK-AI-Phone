<?php


namespace app\api\controller\shanjian;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\validate\shanjian\PublishValidate;
use app\api\validate\shanjian\PublishDetailValidate;
use app\api\logic\shanjian\PublishLogic;
use app\api\lists\shanjian\PublishLists;
use app\api\lists\shanjian\PublishDetailLists;

/**
 * RobotController
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
            $params = (new PublishValidate())->post()->goCheck('delete');
            $result = PublishLogic::delete($params);
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
            $params = (new PublishDetailValidate())->post()->goCheck('delete');
            $result = PublishLogic::recordDelete($params);
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
