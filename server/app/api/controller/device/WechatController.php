<?php


namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\logic\device\WechatLogic;
use app\api\lists\device\WechatLists;
use app\api\validate\device\WechatValidate;

/**
 * WechatController
 * @desc 设备任务
 * @author Qasim
 */
class WechatController extends BaseApiController
{

    public array $notNeedLogin = ['cron'];

    /**
     * @desc 获取列表
     */
    public function lists()
    {
        return $this->dataLists(new WechatLists());
    }


    public function add()
    {
        try {
            $params = (new WechatValidate())->post()->goCheck('add');
            $result = WechatLogic::add($params);
            if ($result) {
                return $this->success(data: WechatLogic::getReturnData());
            }
            return $this->fail(WechatLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new WechatValidate())->post()->goCheck('update');
            $result = WechatLogic::update($params);
            if ($result) {
                return $this->success(data: WechatLogic::getReturnData());
            }
            return $this->fail(WechatLogic::getError());
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
            $params = $this->request->get();
            $result = WechatLogic::detail($params);
            if ($result) {
                return $this->success(data: WechatLogic::getReturnData());
            }
            return $this->fail(WechatLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function cron()
    {
        try {
            WechatLogic::wechatRpaCron();

        } catch (\Throwable $th) {
            //throw $th;
            print_r($th->__toString());
            die;
        }
    }
}
