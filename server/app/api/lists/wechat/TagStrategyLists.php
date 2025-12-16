<?php


namespace app\api\lists\wechat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\AiWechatTagStrategy;

/**
 * 标签策略列表
 * Class TagStrategyLists
 * @package app\api\lists\wechat
 * @author Qasim
 */
class TagStrategyLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['ts.tag_name'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['ts.user_id', '=', $this->userId];
        return AiWechatTagStrategy::alias('ts')
            ->field('ts.id,ts.tag_name,ts.match_type,ts.match_mode,ts.match_keywords,ts.create_time, t.id as tag_id')
            ->join('ai_wechat_tag t','t.tag_name = ts.tag_name and t.user_id = ts.user_id')
            ->where($this->searchWhere)
            ->order('ts.id', 'desc')
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
        $this->searchWhere[] = ['ts.user_id', '=', $this->userId];
        return AiWechatTagStrategy::alias('ts')
            ->join('ai_wechat_tag t','t.tag_name = ts.tag_name and t.user_id = ts.user_id')
            ->where($this->searchWhere)
            ->count();
    }
}
