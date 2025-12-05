<?php


namespace app\api\lists\wechat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\AiWechatRobotKeyword;
use app\common\model\wechat\AiWechat;
use app\common\service\FileService;

/**
 * 微信机器人关键词列表
 * Class RobotLists
 * @package app\api\lists\wechat
 * @author Qasim
 */
class RobotKeywordLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['robot_id']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return AiWechatRobotKeyword::field('id,keyword,reply,match_type,create_time')
            ->where($this->searchWhere)
            ->when($this->request->get('keyword', ''), function ($query) {
                $query->where('keyword', 'like', '%' . $this->request->get('keyword', '') . '%')
                    ->whereOr('reply', 'like', '%' . $this->request->get('keyword', '') . '%');
            })
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {

                $reply = $item->reply;

                // 如果是图片
                foreach ($reply as $key => $value) {

                    if ($value['type'] == 1) {

                        $reply[$key]['content'] = FileService::getFileUrl($value['content']);
                    }
                }

                $item->reply = $reply;
            })
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return AiWechatRobotKeyword::where($this->searchWhere)
            ->when($this->request->get('keyword', ''), function ($query) {
                $query->where('keyword', 'like', '%' . $this->request->get('keyword', '') . '%')
                    ->whereOr('reply', 'like', '%' . $this->request->get('keyword', '') . '%');
            })
            ->count();
    }
}
