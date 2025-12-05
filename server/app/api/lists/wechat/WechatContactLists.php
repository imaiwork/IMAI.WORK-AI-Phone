<?php


namespace app\api\lists\wechat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatContact;

/**
 * 微信好友列表
 * Class WechatLists
 * @package app\api\lists\wechat
 * @author Rick
 */
class WechatContactLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['wechat_id', 'friend_id', 'nickname', 'remark'],
        ];
    }

    /**
     * @notes 获取好友列表
     * @return array
     */
    public function lists(): array
    {
        if (empty($this->searchWhere)) {
            $wechatId = AiWechat::where('user_id', $this->userId)->value('wechat_id');
            $this->searchWhere[] = ['wechat_id', '=', $wechatId];
        }
        return AiWechatContact::field('friend_id, nickname as friend_nickname, avatar as friend_avatar,remark')
                              ->where($this->searchWhere)
                              ->order(['id' => 'desc'])
                              ->limit($this->limitOffset, $this->limitLength)
                              ->select()
                              ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        if (empty($this->searchWhere)) {
            $wechatId = AiWechat::where('user_id', $this->userId)->value('wechat_id');
            $this->searchWhere[] = ['wechat_id', '=', $wechatId];
        }
        return AiWechatContact::where($this->searchWhere)
                              ->count();
    }
}
