<?php


namespace app\api\controller\sora;

use app\api\controller\BaseApiController;
use app\api\lists\sora\PublishDetailLists;
use app\api\lists\sora\PublishLists;
use app\api\logic\sora\PublishLogic;
use think\exception\HttpResponseException;

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
            $params = $this->request->post();
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
            $params = $this->request->post();
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
            $params = $this->request->post();
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
            $params = $this->request->get();
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
            $params = $this->request->get();
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
            $params = $this->request->post();
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
            $params = $this->request->post();
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
            $params = $this->request->post();
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
