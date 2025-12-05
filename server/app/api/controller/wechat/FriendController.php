<?php


namespace app\api\controller\wechat;

use app\api\controller\BaseApiController;
use app\api\lists\wechat\WechatContactLists;
use app\api\logic\wechat\FriendLogic;
use app\api\validate\wechat\FriendValidate;
use think\exception\HttpResponseException;

/**
 * FriendController
 * @desc 微信好友
 * @author Qasim
 */
class FriendController extends BaseApiController
{

    public array $notNeedLogin = [];


    public function lists(){
        return $this->dataLists(new WechatContactLists());
    }

    /**
     * @desc 添加微信好友
     */
    public function add()
    {
        try
        {
            $params = (new FriendValidate())->post()->goCheck('add');
            $result = FriendLogic::addFriend($params);
            if ($result)
            {
                return $this->success(data: FriendLogic::getReturnData());
            }
            return $this->fail(FriendLogic::getError());
        }
        catch (HttpResponseException $e)
        {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 批量添加微信好友
     */
    public function batch()
    {
        try
        {
            $params = (new FriendValidate())->post()->goCheck('batch');
            $result = FriendLogic::batchAddFriend($params);
            if ($result)
            {
                return $this->success(data: FriendLogic::getReturnData());
            }
            return $this->fail(FriendLogic::getError());
        }
        catch (HttpResponseException $e)
        {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 更新微信好友
     */
    public function update()
    {
        try
        {
            $params = (new FriendValidate())->post()->goCheck('update');
            $result = FriendLogic::updateFriend($params);
            if ($result)
            {
                return $this->success(data: FriendLogic::getReturnData());
            }
            return $this->fail(FriendLogic::getError());
        }
        catch (HttpResponseException $e)
        {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 获取微信好友信息
     */
    public function info()
    {
        try
        {
            $params = (new FriendValidate())->get()->goCheck('info');
            $result = FriendLogic::friendDetail($params);
            if ($result)
            {
                return $this->success(data: FriendLogic::getReturnData());
            }
            return $this->fail(FriendLogic::getError());
        }
        catch (HttpResponseException $e)
        {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 删除微信好友
     */
    public function delete()
    {
        try
        {
            $params = (new FriendValidate())->post()->goCheck('delete');
            $result = FriendLogic::deleteFriend($params);
            if ($result)
            {
                return $this->success(data: FriendLogic::getReturnData());
            }
            return $this->fail(FriendLogic::getError());
        }
        catch (HttpResponseException $e)
        {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 接受好友请求
     */
    public function accept()
    {
        $params = $this->request->post();
        $result = FriendLogic::acceptFriend($params);
        if ($result)
        {
            return $this->success(data: FriendLogic::getReturnData());
        }
        return $this->fail(FriendLogic::getError());
    }
}
