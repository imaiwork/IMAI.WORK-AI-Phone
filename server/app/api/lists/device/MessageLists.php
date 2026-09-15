<?php


namespace app\api\lists\device;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvPrivateMessage;

/**
 * 设备消息任务列表
 * Class MessageLists
 * @package app\api\lists\device
 * @author Qasim
 */
class MessageLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['device_code', 'type']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];

        return SvPrivateMessage::field('id, account, type, avatar, author_name, message_content, create_time as message_time, reply_content, reply_time')
            ->where($this->searchWhere)
            ->order('create_time', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['message_content'] = explode("&&", $item['message_content'])[0] ?? $item['message_content'];
                $item['message_time'] = date('Y-m-d H:i:s', $item['message_time']);
                return $item;
            })
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        return SvPrivateMessage::field('id')
            ->where($this->searchWhere)
            ->count();
    }
}
