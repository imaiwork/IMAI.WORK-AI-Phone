<?php


namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\validate\sv\RobotKeywordValidate;
use app\api\logic\sv\RobotKeywordLogic;
use app\api\lists\sv\RobotKeywordLists;

/**
 * RobotKeywordController
 * @desc 机器人关键词
 * @author Qasim
 */
class RobotKeywordController extends BaseApiController
{

    public array $notNeedLogin = [];

    /**
     * @desc 获取关键词列表
     */
    public function lists()
    {
        if (!$this->request->get('robot_id', '')) {
            return $this->fail('机器人ID不能为空');
        }

        return $this->dataLists(new RobotKeywordLists());
    }

    /**
     * @desc 添加机器人关键词
     */
    public function add()
    {
        try {
            $params = (new RobotKeywordValidate())->post()->goCheck('add');
            $result = RobotKeywordLogic::addRobotKeyword($params);
            if ($result) {
                return $this->success(data: RobotKeywordLogic::getReturnData());
            }
            return $this->fail(RobotKeywordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 更新机器人关键词
     */
    public function update()
    {
        try {
            $params = (new RobotKeywordValidate())->post()->goCheck('update');
            $result = RobotKeywordLogic::updateRobotKeyword($params);
            if ($result) {
                return $this->success(data: RobotKeywordLogic::getReturnData());
            }
            return $this->fail(RobotKeywordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 删除机器人关键词
     */
    public function delete()
    {
        try {
            $params = (new RobotKeywordValidate())->post()->goCheck('delete');
            $result = RobotKeywordLogic::deleteRobotKeyword($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(RobotKeywordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 导入机器人关键词
     */
    public function import()
    {
        try {
            $params = (new RobotKeywordValidate())->post()->goCheck('import');
            $result = RobotKeywordLogic::importRobotKeyword($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(RobotKeywordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
