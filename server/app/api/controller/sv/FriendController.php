<?php


namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\logic\sv\FriendLogic;
use app\api\validate\sv\FriendValidate;
use think\exception\HttpResponseException;

/**
 * FriendController
 * @desc 好友
 * @author Qasim
 */
class FriendController extends BaseApiController
{

    public array $notNeedLogin = [];

    /**     
     * @desc 添加好友
     */
    public function add()
    {
        try {
            $params = (new FriendValidate())->post()->goCheck('add');
            $result = FriendLogic::addFriend($params);
            if ($result) {
                return $this->success(data: FriendLogic::getReturnData());
            }
            return $this->fail(FriendLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**     
     * @desc 批量添加好友
     */
    public function batch()
    {
        try {
            $params = (new FriendValidate())->post()->goCheck('batch');
            $result = FriendLogic::batchAddFriend($params);
            if ($result) {
                return $this->success(data: FriendLogic::getReturnData());
            }
            return $this->fail(FriendLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 更新好友
     */
    public function update()
    {
        try {
            $params = (new FriendValidate())->post()->goCheck('update');
            $result = FriendLogic::updateFriend($params);
            if ($result) {
                return $this->success(data: FriendLogic::getReturnData());
            }
            return $this->fail(FriendLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 获取好友信息
     */
    public function info()
    {
        try {
            $params = (new FriendValidate())->get()->goCheck('info');
            $result = FriendLogic::friendDetail($params);
            if ($result) {
                return $this->success(data: FriendLogic::getReturnData());
            }
            return $this->fail(FriendLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 删除好友
     */
    public function delete()
    {
        try {
            $params = (new FriendValidate())->post()->goCheck('delete');
            $result = FriendLogic::deleteFriend($params);
            if ($result) {
                return $this->success(data: FriendLogic::getReturnData());
            }
            return $this->fail(FriendLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
