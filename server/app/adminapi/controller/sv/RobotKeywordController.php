<?php


namespace app\adminapi\controller\sv;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\sv\RobotKeywordLists;
use app\adminapi\logic\sv\RobotKeywordLogic;
use think\exception\HttpResponseException;

/**
 * RobotKeywordController
 * @desc 机器人关键词
 * @author Qasim
 */
class RobotKeywordController extends BaseAdminController
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
            $params = $this->request->post();
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
            $params = $this->request->post();
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
            $params = $this->request->post();
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
            $params = $this->request->post();
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
