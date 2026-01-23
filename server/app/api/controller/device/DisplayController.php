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
            return $this->fail(DisplayLogic::getReturnData());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function lists()
    {
        return $this->dataLists(new DisplayLists());
    }
}