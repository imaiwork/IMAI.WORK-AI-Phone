<?php


namespace app\api\controller;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;

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
}