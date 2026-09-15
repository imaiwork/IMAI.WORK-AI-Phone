<?php


namespace app\api\controller;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;

use app\api\lists\display\IntentionCustomerLists;
use app\api\logic\DisplayLogic;

/**
 * DisplayController
 * @desc 人物显示任务
 * @author Qasim
 */
class DisplayController extends BaseApiController
{
    /**
     * @desc 人物显示
     * @author Qasim
     */
    public function display()
    {
        try {
            $params = $this->request->get();
            $result = DisplayLogic::display($params);
            if ($result) {
                return $this->data(DisplayLogic::getReturnData());
            }
            return $this->fail(DisplayLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function statistics()
    {
        try {
            $params = $this->request->get();
            $result = DisplayLogic::statistics($params);
            if ($result) {
                return $this->data(DisplayLogic::getReturnData());
            }
            return $this->fail(DisplayLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function autoPipeline()
    {
        try {
            $params = $this->request->get();
            if (empty($params['persona_id'])) {
                return $this->fail('人设ID不能为空');
            }
            $result = DisplayLogic::autoPipeline($params);
            if ($result) {
                return $this->data(DisplayLogic::getReturnData());
            }
            return $this->fail(DisplayLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function intentionStatistics()
    {
        try {
            $params = $this->request->get();
            $result = DisplayLogic::intentionStatistics($params);
            if ($result) {
                return $this->data(DisplayLogic::getReturnData());
            }
            return $this->fail(DisplayLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function intentionCustomerLists()
    {
        try {
            return $this->dataLists(new IntentionCustomerLists());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function privateMessageRecord()
    {
        try {
            $params = $this->request->get();
            $result = DisplayLogic::privateMessageRecord($params);
            if ($result) {
                return $this->data(DisplayLogic::getReturnData());
            }
            return $this->fail(DisplayLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function followRecord()
    {
        try {
            $params = $this->request->get();
            $result = DisplayLogic::followRecord($params);
            if ($result) {
                return $this->data(DisplayLogic::getReturnData());
            }
            return $this->fail(DisplayLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function circleInteractionDetail()
    {
        try {
            $params = $this->request->get();
            $result = DisplayLogic::circleInteractionDetail($params);
            if ($result) {
                return $this->data(DisplayLogic::getReturnData());
            }
            return $this->fail(DisplayLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
