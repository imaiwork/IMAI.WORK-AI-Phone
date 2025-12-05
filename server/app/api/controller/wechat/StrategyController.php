<?php


namespace app\api\controller\wechat;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\validate\wechat\StrategyValidate;
use app\api\logic\wechat\StrategyLogic;
use app\api\lists\wechat\TagStrategyLists;

/**
 * StrategyController
 * @desc 微信回复策略
 * @author Qasim
 */
class StrategyController extends BaseApiController
{

    public array $notNeedLogin = ['exec'];


    /**
     * @desc 回复策略
     */
    public function reply()
    {
        try
        {
            $params = (new StrategyValidate())->post()->goCheck('reply');
            $result = StrategyLogic::replyStrategy($params);
            if ($result)
            {
                return $this->success(data: StrategyLogic::getReturnData());
            }
            return $this->fail(StrategyLogic::getError());
        }
        catch (HttpResponseException $e)
        {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 回复策略信息
     */
    public function replyInfo()
    {
        $result = StrategyLogic::replyInfo();
        if ($result)
        {
            return $this->success(data: StrategyLogic::getReturnData());
        }
        return $this->fail(StrategyLogic::getError());
    }

    /**
     * @desc 打招呼策略
     */
    public function greet()
    {
        try
        {
            $params = (new StrategyValidate())->post()->goCheck('greet');
            $result = StrategyLogic::greetStrategy($params);
            if ($result)
            {
                return $this->success(data: StrategyLogic::getReturnData());
            }
            return $this->fail(StrategyLogic::getError());
        }
        catch (HttpResponseException $e)
        {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 打招呼策略信息
     */
    public function greetInfo()
    {
        $result = StrategyLogic::greetInfo();
        if ($result)
        {
            return $this->success(data: StrategyLogic::getReturnData());
        }
        return $this->fail(StrategyLogic::getError());
    }

    /**
     * @desc 标签策略
     */
    public function tag()
    {
        $params = $this->request->post();
        $result = StrategyLogic::tagStrategy($params);
        if ($result)
        {
            return $this->success(data: StrategyLogic::getReturnData());
        }
        return $this->fail(StrategyLogic::getError());
    }

    /**
     * @desc 标签策略信息
     */
    public function tagInfo()
    {
        $id = $this->request->get('id');
        $result = StrategyLogic::tagInfo($id);
        if ($result)
        {
            return $this->success(data: StrategyLogic::getReturnData());
        }
        return $this->fail(StrategyLogic::getError());
    }

    /**
     * @desc 标签策略导入
     */
    public function tagImport()
    {
        $params = $this->request->post();
        $result = StrategyLogic::tagImport($params);
        if ($result)
        {
            return $this->success(data: StrategyLogic::getReturnData());
        }
        return $this->fail(StrategyLogic::getError());
    }

    /**
     * @desc 标签策略导入
     */
    public function tagDelete()
    {
        $id = $this->request->post('id');
        $result = StrategyLogic::tagDelete($id);
        if ($result)
        {
            return $this->success();
        }
        return $this->fail(StrategyLogic::getError());
    }

    /**
     * @desc 标签策略导入
     */
    public function tagUpdate()
    {
        $params = $this->request->post();
        $result = StrategyLogic::tagUpdate($params);
        if ($result)
        {
            return $this->success(data: StrategyLogic::getReturnData());
        }
        return $this->fail(StrategyLogic::getError());
    }

    /**
     * @desc 标签策略列表
     */
    public function tagLists()
    {
        return $this->dataLists(new TagStrategyLists());
    }



    /**
     * @desc 自动通过好友策略
     */
    public function acceptFriend()
    {
        try
        {
            $params = (new StrategyValidate())->post()->goCheck('reply');
            $result = StrategyLogic::acceptFriendStrategy($params);
            if ($result)
            {
                return $this->success(data: StrategyLogic::getReturnData());
            }
            return $this->fail(StrategyLogic::getError());
        }
        catch (HttpResponseException $e)
        {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 自动接受好友策略信息
     */
    public function acceptFriendInfo()
    {
        $result = StrategyLogic::acceptFriendInfo();
        if ($result)
        {
            return $this->success(data: StrategyLogic::getReturnData());
        }
        return $this->fail(StrategyLogic::getError());
    }


    /**
     * @desc 朋友圈点赞策略
     */
    public function circleReplyLike()
    {
        $params = $this->request->post();
        $result = StrategyLogic::circleReplyLikeStrategy($params);
        if ($result)
        {
            return $this->success(data: StrategyLogic::getReturnData());
        }
        return $this->fail(StrategyLogic::getError());
    }

    /**
     * @desc 朋友圈点赞策略信息
     */
    public function circleReplyLikeInfo()
    {
        
        $result = StrategyLogic::circleReplyLikeInfo();
        if ($result)
        {
            return $this->success(data: StrategyLogic::getReturnData());
        }
        return $this->fail(StrategyLogic::getError());
    }

    public function exec()
    {
        try {
            StrategyLogic::execCircleReplyLikeStrategy();
            return $this->success();
        } catch (\Throwable $th) {
            return $this->fail($th->getMessage());
        }
    }

}
