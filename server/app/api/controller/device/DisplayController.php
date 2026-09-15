<?php


namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;

use app\api\logic\device\DisplayLogic;
use app\api\lists\device\DisplayLists;

/**
 * DisplayController
 * @desc 设备显示任务
 * @author Qasim
 */
class DisplayController extends BaseApiController
{
    /**
     * @desc 设备显示
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

    public function lists()
    {
        return $this->dataLists(new DisplayLists());
    }

    public function cluesDetail()
    {
        try {
            $params = $this->request->get();
            $result = DisplayLogic::cluesDetail($params);
            if ($result) {
                return $this->data(DisplayLogic::getReturnData());
            }
            return $this->fail(DisplayLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
    public function touchDetail()
    {
        try {
            $params = $this->request->get();
            $result = DisplayLogic::touchDetail($params);
            if ($result) {
                return $this->data(DisplayLogic::getReturnData());
            }
            return $this->fail(DisplayLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function wechatCircleThumbCommentDetail()
    {
        try {
            $params = $this->request->get();
            $result = DisplayLogic::wechatCircleThumbCommentDetail($params);
            if ($result) {
                return $this->data(DisplayLogic::getReturnData());
            }
            return $this->fail(DisplayLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function cluesWechatDetail()
    {
        try {
            $params = $this->request->get();
            $result = DisplayLogic::cluesWechatDetail($params);
            if ($result) {
                return $this->data(DisplayLogic::getReturnData());
            }
            return $this->fail(DisplayLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

}