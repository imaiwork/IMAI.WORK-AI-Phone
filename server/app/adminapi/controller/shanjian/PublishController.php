<?php

namespace app\adminapi\controller\shanjian;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\shanjian\PublishLists;
use app\adminapi\logic\shanjian\PublishLogic;
use app\adminapi\lists\shanjian\PublishDetailLists;
use think\exception\HttpResponseException;

class PublishController extends BaseAdminController
{
    /**
     * @notes 列表
     * @author Lee
     * @date 2025-05-14 09:40:09
     */
    public function lists()
    {
        return $this->dataLists(new PublishLists());
    }


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

    public function change()
    {
        try {
            $params = $this->request->post();
            $result = PublishLogic::change($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

        /**
     * @notes 详情
     * @author Lee
     * @date 2025-05-14 09:40:09
     */
    public function detail() {
        try {
            $params  = $this->request->get();
            $result = PublishLogic::detail($params);
            if ($result) {
                return $this->data(PublishLogic::getReturnData());
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function recordLists()
    {
        return $this->dataLists(new PublishDetailLists());
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


    public function recordDetail()
    {
        try {
            $params  = $this->request->get();
            $result = PublishLogic::recordDetail($params);
            if ($result) {
                return $this->data(PublishLogic::getReturnData());
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
