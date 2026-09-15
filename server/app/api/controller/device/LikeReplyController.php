<?php


namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;

use app\api\validate\device\LikeReplyValidate;
use app\api\logic\device\LikeReplyLogic;
use app\api\lists\device\LikeReplyLists;

/**
 * LikeReplyController
 * @desc 设备点赞回复任务
 * @author Qasim
 */
class LikeReplyController extends BaseApiController
{

    public array $notNeedLogin = ['cron'];

    /**
     * @desc 获取列表
     */
    public function lists()
    {
        //return $this->dataLists(new LikeReplyLists());
    }

    public function add()
    {
        try {
            $params = (new LikeReplyValidate())->post()->goCheck('add');
            $result = LikeReplyLogic::add($params);
            if ($result) {
                return $this->success(data: LikeReplyLogic::getReturnData());
            }
            return $this->fail(LikeReplyLogic::getError());   
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update() {}

    /**
     * @desc 删除机器人
     */
    public function delete()
    {
        try {
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
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function cron()
    {
        try {
            //DeviceActiveLogic::execDeviceActiveCron();

        } catch (\Throwable $th) {
            //throw $th;
            print_r($th->__toString());
            die;
        }
    }
}
