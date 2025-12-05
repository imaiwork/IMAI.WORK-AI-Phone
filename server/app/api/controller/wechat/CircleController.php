<?php


namespace app\api\controller\wechat;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\validate\wechat\TodoValidate;
use app\api\logic\wechat\CircleLogic;
use app\api\lists\wechat\CircleTaskLists;

/**
 * CircleController
 * @desc 微信朋友圈
 * @author Qasim
 */
class CircleController extends BaseApiController
{

    public array $notNeedLogin = [];

    /**
     * @desc 获取发圈任务列表
     */
    public function taskLists()
    {
        return $this->dataLists(new CircleTaskLists());
    }

    /**
     * @desc 添加任务
     */
    public function addTask()
    {
        $params = $this->request->post();
        $result = CircleLogic::addTask($params);
        if ($result)
        {
            return $this->success();
        }
        return $this->fail(CircleLogic::getError());
    }

    /**
     * @desc 更新任务
     */
    public function updateTask()
    {
        $params = $this->request->post();
        $result = CircleLogic::updateTask($params);
        if ($result)
        {
            return $this->success(data: CircleLogic::getReturnData());
        }
        return $this->fail(CircleLogic::getError());
    }

    /**
     * @desc 删除任务
     */
    public function deleteTask()
    {
        try
        {
            $params = (new TodoValidate())->post()->goCheck('delete');
            $result = CircleLogic::deleteTask($params);
            if ($result)
            {
                return $this->success();
            }
            return $this->fail(CircleLogic::getError());
        }
        catch (HttpResponseException $e)
        {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 任务详情
     */
    public function taskInfo()
    {
        $id = $this->request->get('id', 0);
        $result = CircleLogic::taskInfo($id);
        if ($result)
        {
            return $this->success(data: CircleLogic::getReturnData());
        }
        return $this->fail(CircleLogic::getError());
    }
}
