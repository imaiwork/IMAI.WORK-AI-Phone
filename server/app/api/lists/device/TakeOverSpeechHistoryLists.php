<?php


namespace app\api\lists\device;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvDeviceTakeOverSpeechHistory;

/**
 * 设备接管语音历史列表
 * Class TakeOverSpeechHistoryLists
 * @package app\api\lists\device
 * @author Qasim
 */
class TakeOverSpeechHistoryLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['type'],
            '%like%' => ['keyword']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];

        return SvDeviceTakeOverSpeechHistory::field('*')
            ->where($this->searchWhere)
            ->order('id', 'desc')
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
        return SvDeviceTakeOverSpeechHistory::field('*')
            ->where($this->searchWhere)
            ->count();
    }
}

