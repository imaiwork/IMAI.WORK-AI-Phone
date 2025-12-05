<?php


namespace app\api\controller\wechat;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\validate\wechat\TodoValidate;
use app\api\logic\wechat\TodoLogic;
use app\api\lists\wechat\TodoLists;

/**
 * TodoController
 * @desc 微信代办
 * @author Qasim
 */
class TodoController extends BaseApiController
{

    public array $notNeedLogin = [];

    /**
     * @desc 获取微信列表
     */
    public function lists()
    {
        return $this->dataLists(new TodoLists());
    }

    /**
     * @desc 添加待办
     */
    public function add()
    {
        try {
            $params = (new TodoValidate())->post()->goCheck('add');
            $result = TodoLogic::addTodo($params);
            if ($result) {
                return $this->success(data: TodoLogic::getReturnData());
            }
            return $this->fail(TodoLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 添加待办
     */
    public function update()
    {
        try {
            $params = (new TodoValidate())->post()->goCheck('update');
            $result = TodoLogic::updateTodo($params);
            if ($result) {
                return $this->success(data: TodoLogic::getReturnData());
            }
            return $this->fail(TodoLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 删除
     */
    public function delete()
    {
        try {
            $params = (new TodoValidate())->post()->goCheck('delete');
            $result = TodoLogic::deleteTodo($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(TodoLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 推送消息
     */
    public function push()
    {
        try {
            TodoLogic::pushMessageCron();
            return $this->success();
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
