<?php
namespace app\adminapi\controller\sv;
use app\adminapi\lists\sv\RobotLists;
use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\sv\RobotLogic;
use app\adminapi\validate\sv\RobotValidate;
use think\exception\HttpResponseException;

class RobotController extends BaseAdminController
{
     /**
     * @notes 列表
     * @author Lee
     * @date 2025-05-14 09:40:09
     */
    public function lists()
    {
        return $this->dataLists(new RobotLists());
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
}