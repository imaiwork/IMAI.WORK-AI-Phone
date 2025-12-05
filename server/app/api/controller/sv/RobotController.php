<?php


namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\common\service\ConfigService;
use app\common\service\FileService;
use think\exception\HttpResponseException;
use app\api\validate\sv\RobotValidate;
use app\api\logic\sv\RobotLogic;
use app\api\lists\sv\RobotLists;

/**
 * RobotController
 * @desc 机器人
 * @author Qasim
 */
class RobotController extends BaseApiController
{

    public array $notNeedLogin = [];

    /**
     * @desc 获取列表
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
    public function remove()
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
     * @desc 详情
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

    /**
     * @desc 添加机器人
     */
    public function defaultAdd()
    {
        try {
            $web_logo = FileService::getFileUrl(ConfigService::get('website', 'web_logo'));
            $params = [
                "id"=>"",
                "logo"=>$web_logo,
                "name"=>"默认助理",
                "profile"=>"默认助理描述",
                "description"=>"",
                "company_background"=>"",
                "index_id"=>""
            ];
            $result = RobotLogic::addRobot($params);
            if ($result) {
                return $this->success(data: RobotLogic::getReturnData());
            }
            return $this->fail(RobotLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
