<?php


namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;

use app\api\validate\device\TakeOverValidate;
use app\api\logic\device\TakeOverLogic;
use app\api\lists\device\TakeOverLists;
use app\api\lists\device\TakeOverSpeechHistoryLists;

/**
 * TakeOverController
 * @desc 设备接管任务
 * @author Qasim
 */
class TakeOverController extends BaseApiController
{

    public array $notNeedLogin = ['cron'];

    /**
     * @desc 获取列表
     */
    public function lists()
    {
        return $this->dataLists(new TakeOverLists());
    }

    public function speechHistory()
    {
        return $this->dataLists(new TakeOverSpeechHistoryLists());
    }

    public function speechDelete()
    {
        try {
            $params = $this->request->post();
            $result = TakeOverLogic::speechDelete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(TakeOverLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function add()
    {
        try {
            $params = (new TakeOverValidate())->post()->goCheck('add');
            $result = TakeOverLogic::add($params);
            if ($result) {
                return $this->success(data: TakeOverLogic::getReturnData());
            }
            return $this->fail(TakeOverLogic::getError());
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

    /**
     * @desc 读取加群触发关键词
     */
    public function groupTriggerKeywords()
    {
        try {
            $result = TakeOverLogic::getGroupTriggerKeywords();
            if ($result) {
                return $this->success(data: TakeOverLogic::getReturnData());
            }
            return $this->fail(TakeOverLogic::getError());
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
