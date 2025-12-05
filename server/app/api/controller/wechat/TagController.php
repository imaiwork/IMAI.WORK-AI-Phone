<?php


namespace app\api\controller\wechat;

use app\api\controller\BaseApiController;
use app\api\lists\wechat\TagFriendLists;
use app\api\lists\wechat\TagLists;
use app\api\logic\wechat\TagLogic;

/**
 * TagController
 * @desc 微信标签
 * @author Qasim
 */
class TagController extends BaseApiController
{

    public array $notNeedLogin = [];


    /**
     * @desc 标签列表
     */
    public function lists()
    {
        return $this->dataLists(new TagLists());
    }

    /**
     * @desc 标签好友列表
     */
    public function friendLists()
    {
        return $this->dataLists(new TagFriendLists());
    }

    /**
     * @desc 给好友打标签
     */
    public function batchFriends()
    {
        $tagIds = $this->request->post('tag_ids', []);
        $friendIds = $this->request->post('friend_ids', []);
        $wechatId = $this->request->post('wechat_id','');

        if (empty($friendIds))
        {
            return $this->fail('好友ID集合不能为空');
        }

        $result = TagLogic::tagFriends($tagIds, $friendIds, $wechatId);
        if ($result)
        {
            return $this->success(data: TagLogic::getReturnData());
        }
        return $this->fail(TagLogic::getError());
    }

    /**
     * @desc 更新标签
     */
    public function update()
    {
        $params = $this->request->post();

        $result = TagLogic::tagUpdate($params);
        if ($result)
        {
            return $this->success(data: TagLogic::getReturnData());
        }
        return $this->fail(TagLogic::getError());
    }

    /**
     * @desc 删除标签
     */
    public function delete()
    {
        $id = $this->request->post('id', 0);

        $result = TagLogic::tagDelete($id);
        if ($result)
        {
            return $this->success();
        }
        return $this->fail(TagLogic::getError());
    }

    /**
     * @desc 查看好友标签
     */
    public function friendTagDetail(){
        $params = $this->request->post();
        $result = TagLogic::friendTagDetail($params);
        if ($result)
        {
            return $this->success(data: TagLogic::getReturnData());
        }
        return $this->fail(TagLogic::getError());
    }

    /**
     * @desc 编辑好友标签
     */
    public function friendTagUpdate(){
        $params = $this->request->post();
        $result = TagLogic::friendTagUpdate($params);
        if ($result)
        {
            return $this->success(data: TagLogic::getReturnData());
        }
        return $this->fail(TagLogic::getError());
    }

    /**
     * @desc 删除好友标签
     */
    public function friendTagDelete(){
        $params = $this->request->post();
        $result = TagLogic::friendTagDelete($params);
        if ($result)
        {
            return $this->success(data: TagLogic::getReturnData());
        }
        return $this->fail(TagLogic::getError());
    }
}
