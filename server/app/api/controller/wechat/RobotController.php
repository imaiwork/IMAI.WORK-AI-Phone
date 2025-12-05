<?php


namespace app\api\controller\wechat;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\validate\wechat\RobotValidate;
use app\api\logic\wechat\RobotLogic;
use app\api\lists\wechat\RobotLists;

/**
 * RobotController
 * @desc 微信机器人
 * @author Qasim
 */
class RobotController extends BaseApiController
{

    public array $notNeedLogin = [];

    /**
     * @desc 获取微信列表
     */
    public function lists()
    {
        return $this->dataLists(new RobotLists());
    }

    /**
     * @desc 添加机器人
     */
    public function add()
    {
        try {
            $params = (new RobotValidate())->post()->goCheck('add');
            $result = RobotLogic::addRobot($params);
            if ($result) {
                return $this->success(data: RobotLogic::getReturnData());
            }
            return $this->fail(RobotLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 更新机器人
     */
    public function update()
    {
        try {
            $params = (new RobotValidate())->post()->goCheck('update');
            $result = RobotLogic::updateRobot($params);
            if ($result) {
                return $this->success(data: RobotLogic::getReturnData());
            }
            return $this->fail(RobotLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 删除机器人
     */
    public function delete()
    {
        try {
            $params = (new RobotValidate())->post()->goCheck('delete');
            $result = RobotLogic::deleteRobot($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(RobotLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 删除机器人
     */
    public function detail()
    {
        try {
            $params = (new RobotValidate())->get()->goCheck('detail');
            $result = RobotLogic::detailRobot($params);
            if ($result) {
                return $this->data(RobotLogic::getReturnData());
            }
            return $this->fail(RobotLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
