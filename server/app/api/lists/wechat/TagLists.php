<?php

namespace app\api\lists\wechat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\AiWechatTag;

/**
 * 标签列表及统计
 * Class TagLists
 * @package app\api\lists\wechat
 * @author Qasim
 */
class TagLists extends BaseApiDataLists implements ListsSearchInterface
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
        $this->searchWhere[] = ['user_id', '=', $this->userId];

        // 获取微信ID
        $wechatId = $this->request->get('wechat_id');

        // 获取标签列表
        $tagList = AiWechatTag::where('user_id', $this->userId)
            ->field('id, tag_name, create_time')
            ->where($this->searchWhere)
            ->limit($this->limitOffset, $this->limitLength)
            ->order('create_time', 'desc')
            ->select()
            ->each(function ($item) use ($wechatId)
            {
                $item->friend_count = count($item->friendIds($wechatId, $item['id']));
            })
            ->toArray();

        if ($this->request->get('page_no', 1) == 1)
        {

            $untagFriendIds = app(AiWechatTag::class)->untagFriendIds($wechatId);
            // 数组上方添加未打标签的好友数量
            array_unshift($tagList, [
                'id' => 0,
                'tag_name' => '未打标签好友',
                'friend_count' => count($untagFriendIds),
            ]);
        }

        return $tagList;
    }

    /**
     * @notes 获取标签数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return AiWechatTag::where($this->searchWhere)->count();
    }
}
