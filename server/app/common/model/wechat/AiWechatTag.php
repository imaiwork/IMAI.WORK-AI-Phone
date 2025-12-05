<?php

namespace app\common\model\wechat;

use app\common\model\BaseModel;
use app\common\model\wechat\AiWechatFriendTag;


class AiWechatTag extends BaseModel
{

    /**
     * 获取指定微信ID和标签ID的好友ID列表
     *
     * @param string $wechatId 微信ID
     * @param array|string $tagIds 标签ID或标签ID数组
     * @return array 好友ID列表
     */
    public function friendIds(string $wechatId, array|string $tagIds = []): array
    {
        // 当 $tagIds 是数组时，求交集
        if (is_array($tagIds))
        {
            sort($tagIds);
            $expectedTagsStr = implode(',', $tagIds);
            $friendIds = AiWechatFriendTag::where('wechat_id', $wechatId)
                ->whereIn('tag_id', $tagIds)
                ->group('friend_id')
                //->having("GROUP_CONCAT(DISTINCT tag_id ORDER BY tag_id ASC) = '{$expectedTagsStr}'")
                ->column('friend_id');
            
        }
        else
        {
            // 如果 $tagIds 是单个值，直接返回该标签的所有好友
            $friendIds = AiWechatFriendTag::where('wechat_id', $wechatId)
                ->whereIn('tag_id', $tagIds)
                ->group('friend_id')
                ->column('friend_id');
        }

        return $friendIds;
    }


    /**
     * 获取指定微信ID和标签ID的好友ID列表
     *
     * @param string $wechatId 微信ID
     * @return array 好友ID列表
     */
    public function untagFriendIds(string $wechatId): array
    {

        // 获取已打标签的好友ID
        $friendIds = AiWechatFriendTag::where('wechat_id', $wechatId)
            ->group('friend_id')
            ->column('friend_id');

        // 获取所有好友ID
        $allFriendIds = AiWechatContact::where('wechat_id', $wechatId)
            ->when($friendIds, function ($query) use ($friendIds)
            {
                $query->whereNotIn('friend_id', $friendIds);
            })
            ->group('friend_id')
            ->column('friend_id');

        return $allFriendIds;
    }
}
