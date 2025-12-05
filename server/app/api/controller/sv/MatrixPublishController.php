<?php


namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\validate\sv\MatrixPublishValidate;
use app\api\validate\sv\MatrixPublishDetailValidate;
use app\api\logic\sv\MatrixPublishLogic;
use app\api\lists\sv\MatrixPublishLists;
use app\api\lists\sv\MatrixPublishDetailLists;

/**
 * RobotController
 * @desc 发布设置
 * @author Qasim
 */
class MatrixPublishController extends BaseApiController
{

    public array $notNeedLogin = ['setPublishDetail'];

    /**
     * @desc 获取列表
     */
    public function lists()
    {
        return $this->dataLists(new MatrixPublishLists());
    }

    /**
     * @desc 添加发布设置
     */
    public function add()
    {
        try {
            $params = (new MatrixPublishValidate())->post()->goCheck('add');
            $result = MatrixPublishLogic::add($params);
            if ($result) {
                return $this->success(data: MatrixPublishLogic::getReturnData());
            }
            return $this->fail(MatrixPublishLogic::getError());
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
            $params = (new MatrixPublishValidate())->post()->goCheck('update');
            $result = MatrixPublishLogic::update($params);
            if ($result) {
                return $this->success(data: MatrixPublishLogic::getReturnData());
            }
            return $this->fail(MatrixPublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function change()
    {
        try {
            $params = (new MatrixPublishValidate())->post()->goCheck('change');
            $result = MatrixPublishLogic::change($params);
            if ($result) {
                return $this->success(data: MatrixPublishLogic::getReturnData());
            }
            return $this->fail(MatrixPublishLogic::getError());
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
            $params = (new MatrixPublishValidate())->post()->goCheck('delete');
            $result = MatrixPublishLogic::delete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(MatrixPublishLogic::getError());
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
            $params = (new MatrixPublishValidate())->get()->goCheck('detail');
            $result = MatrixPublishLogic::detail($params);
            if ($result) {
                return $this->data(MatrixPublishLogic::getReturnData());
            }
            return $this->fail(MatrixPublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function recordLists(){

        return $this->dataLists(new MatrixPublishDetailLists());
    }

    public function recordDetail(){

        try {
            $params = (new MatrixPublishDetailValidate())->get()->goCheck('detail');
            $result = MatrixPublishLogic::recordDetail($params);
            if ($result) {
                return $this->data(MatrixPublishLogic::getReturnData());
            }
            return $this->fail(MatrixPublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function recordDelete()
    {
        try {
            $params = (new MatrixPublishDetailValidate())->post()->goCheck('delete');
            $result = MatrixPublishLogic::recordDelete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(MatrixPublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function recordRetry(){

        try {
            $params = (new MatrixPublishDetailValidate())->post()->goCheck('retry');
            $result = MatrixPublishLogic::recordRetry($params);
            if ($result) {
                return $this->data(MatrixPublishLogic::getReturnData());
            }
            return $this->fail(MatrixPublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function recordRepublish(){
        try {
            $params = (new MatrixPublishDetailValidate())->post()->goCheck('republish');
            $result = MatrixPublishLogic::recordRepublish($params);
            if ($result) {
                return $this->data(MatrixPublishLogic::getReturnData());
            }
            return $this->fail(MatrixPublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function testAdd(){
        try {
            $params = (new MatrixPublishDetailValidate())->post()->goCheck('test');
            $result = MatrixPublishLogic::testAdd($params);
            if ($result) {
                return $this->success(data: MatrixPublishLogic::getReturnData());
            }
            return $this->fail(MatrixPublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function testPublish(){
        try {
            $params = $this->request->post();
            $result = MatrixPublishLogic::testPublish($params);
            if ($result) {
                return $this->success(data: MatrixPublishLogic::getReturnData());
            }
            return $this->fail(MatrixPublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    
    public function setPublishDetail(){

        MatrixPublishLogic::setPublishDetail();
    }
}
