<?php

namespace app\api\lists\wechat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatContact;
use app\common\model\wechat\AiWechatTag;

/**
 * 标签好友列表
 * Class TagFriendLists
 * @package app\api\lists\wechat
 * @author Qasim
 */
class TagFriendLists extends BaseApiDataLists implements ListsSearchInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [];
    }

    /**
     * @notes 获取标签列表
     * @return array
     */
    public function lists(): array
    {
        // 获取微信ID
        $wechatId = $this->request->get('wechat_id');

        // 获取标签ID
        if ($tagIds = $this->request->get('tag_ids'))
        {
            $res = strpos($tagIds, ',');
            if ($res){
                $tagIds = explode(',', $tagIds);
            }
            $friendIds = app(AiWechatTag::class)->friendIds($wechatId, $tagIds);
        }
        else
        {
            $friendIds = app(AiWechatTag::class)->untagFriendIds($wechatId);
        }
        //print_r($friendIds);die;
        // 获取好友列表
        return AiWechatContact::where('wechat_id', $wechatId)
            ->field('friend_id, nickname as friend_nickname, avatar as friend_avatar')
            ->whereIn('friend_id', $friendIds)
            ->when($this->request->get('friend_nickname'), function ($query)
            {
                $query->where('nickname', 'like', '%' . $this->request->get('friend_nickname') . '%');
            })
            ->limit($this->limitOffset, $this->limitLength)
            ->order('create_time', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 获取标签数量
     * @return int
     */
    public function count(): int
    {
         // 获取微信ID
        $wechatId = AiWechat::where('user_id', $this->userId)->value('wechat_id', '');
        $wechatId = !empty($this->request->get('wechat_id')) ? $this->request->get('wechat_id') : $wechatId;

        // 获取标签ID
        if ($tagIds = $this->request->get('tag_ids'))
        {
            $res = strpos($tagIds, ',');
            if ($res){
                $tagIds = explode(',', $tagIds);
            }
            $friendIds = app(AiWechatTag::class)->friendIds($wechatId, $tagIds);
        }
        else
        {
            $friendIds = app(AiWechatTag::class)->untagFriendIds($wechatId);
        }
        //print_r($friendIds);die;
        // 获取好友列表
        return AiWechatContact::where('wechat_id', $wechatId)
            ->field('*')
            ->whereIn('friend_id', $friendIds)
            ->when($this->request->get('friend_nickname'), function ($query)
            {
                $query->where('nickname', 'like', '%' . $this->request->get('friend_nickname') . '%');
            })
            ->count();
    }
}
