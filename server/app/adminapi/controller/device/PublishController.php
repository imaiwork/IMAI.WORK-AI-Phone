<?php
namespace app\adminapi\controller\device;

use app\adminapi\validate\device\PublishDetailValidate;
use app\adminapi\validate\device\PublishValidate;
use think\exception\HttpResponseException;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\device\PublishLists;
use app\adminapi\logic\device\PublishLogic;
use app\adminapi\lists\device\PublishDetailLists;

class PublishController extends BaseAdminController
{

    public array $notNeedLogin = ['setPublishDetail'];

    /**
     * @desc 获取列表
     */
    public function lists()
    {
        return $this->dataLists(new PublishLists());
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

            $result = PublishLogic::recordDelete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


}

