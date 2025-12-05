<?php


namespace app\api\controller\wechat;

use app\api\controller\BaseApiController;
use app\api\logic\wechat\WechatLogic;
use app\api\validate\wechat\WechatValidate;
use app\api\lists\wechat\WechatLists;
use think\exception\HttpResponseException;

/**
 * WechatController
 * @desc 微信
 * @author Qasim
 */
class WechatController extends BaseApiController
{

    public array $notNeedLogin = [];

    /**
     * @desc 获取微信列表
     */
    public function lists()
    {
        try {
            return $this->dataLists(new WechatLists());
        }catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
        
    }

    /**
     * @desc 添加微信
     */
    public function add()
    {
        try {
            $params = (new WechatValidate())->post()->goCheck('add');
            $result = WechatLogic::addWechat($params);
            if ($result) {
                return $this->success(data: WechatLogic::getReturnData());
            }
            return $this->fail(WechatLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 更新微信
     */
    public function update()
    {
        try {
            $params = (new WechatValidate())->post()->goCheck('update');
            $result = WechatLogic::updateWechat($params);
            if ($result) {
                return $this->success(data: WechatLogic::getReturnData());
            }
            return $this->fail(WechatLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 更新微信
     */
    public function ai()
    {
        try {
            $params = (new WechatValidate())->post()->goCheck('ai');
            $result = WechatLogic::updateWechatAi($params);
            if ($result) {
                return $this->success(data: WechatLogic::getReturnData());
            }
            return $this->fail(WechatLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 下线微信
     */
    public function offline()
    {
        try {
            $params = (new WechatValidate())->post()->goCheck('offline');
            $result = WechatLogic::offlineWechat($params);
            if ($result) {
                return $this->success(data: WechatLogic::getReturnData());
            }
            return $this->fail(WechatLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }



    /**
     * @desc 获取微信详情
     */
    public function detail()
    {
        try {
            $params = (new WechatValidate())->get()->goCheck('detail');
            $result = WechatLogic::detailWechat($params);
            if ($result) {
                return $this->data(WechatLogic::getReturnData());
            }
            return $this->fail(WechatLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
