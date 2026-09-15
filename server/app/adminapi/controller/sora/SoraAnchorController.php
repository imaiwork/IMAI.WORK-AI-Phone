<?php

namespace app\adminapi\controller\sora;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\sora\SoraAnchorLists;
use app\adminapi\logic\sora\SoraAnchorLogic;
use think\exception\HttpResponseException;

class SoraAnchorController extends BaseAdminController
{
    public function lists()
    {
        return $this->dataLists(new SoraAnchorLists());
    }

    public function update()
    {
        try {
            $params = $this->request->post();
            $result = SoraAnchorLogic::update($params);
            if ($result) {
                return $this->data(SoraAnchorLogic::getReturnData());
            }
            return $this->fail(SoraAnchorLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function delete()
    {
        try {
            $params = $this->request->post();
            $result = SoraAnchorLogic::delete($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SoraAnchorLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = $this->request->get();
            $result = SoraAnchorLogic::detail($params);
            if ($result) {
                return $this->data(SoraAnchorLogic::getReturnData());
            }
            return $this->fail(SoraAnchorLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

}


