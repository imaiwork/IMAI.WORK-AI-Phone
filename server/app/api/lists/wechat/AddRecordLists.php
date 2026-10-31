<?php


namespace app\api\lists\wechat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\AiWechat;
use app\common\model\wechat\AiWechatAddRecord;

/**
 * AI销售加微记录列表
 * Class AddRecordLists
 * @package app\api\lists\wechat
 * @author Qasim
 */
class AddRecordLists extends BaseApiDataLists implements ListsSearchInterface
{
    private array $channel = array(
        1 => '视频号',
        3 => '小红书',
        4 => '抖音获客',
        5 => '快手',
    );

    public function setSearch(): array
    {
        return [
            '=' => ['wechat_id', 'status', 'channel'],
            '%like%' => ['friend_wechat'],
            'between_time' => 'create_time',
        ];
    }

    /**
     * @notes 获取加微记录列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $records = AiWechatAddRecord::field('id,user_id,record_id,task_id,wechat_id,device_code,friend_wechat,channel,message,status,result,exec_time,create_time,update_time')
            ->where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select();

        // 一次性映射微信信息，避免逐行查询
        $wechatMap = [];
        $wechatIds = array_values(array_unique(array_filter(array_column($records->toArray(), 'wechat_id'))));
        if (!empty($wechatIds)) {
            AiWechat::field('wechat_id,wechat_nickname,wechat_avatar')
                ->where('user_id', $this->userId)
                ->where('wechat_id', 'in', $wechatIds)
                ->select()
                ->each(function ($item) use (&$wechatMap) {
                    $wechatMap[$item['wechat_id']] = $item;
                });
        }

        return $records->each(function ($item) use ($wechatMap) {
            $item['wechat_nickname'] = $wechatMap[$item['wechat_id']]['wechat_nickname'] ?? '';
            $item['wechat_avatar'] = $wechatMap[$item['wechat_id']]['wechat_avatar'] ?? '';
            $item['channel_name'] = $this->channel[(int)$item['channel']] ?? '/';
        })->toArray();
    }


    /**
     * @notes 获取加微记录数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return AiWechatAddRecord::where($this->searchWhere)->count();
    }
}
