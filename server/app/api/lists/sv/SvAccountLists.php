<?php


namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvAccount;
use app\api\logic\sv\MessageLogic;
use app\common\model\sv\SvMaterial;
use app\common\model\kb\KbRobot;

/**
 * 列表
 * Class SvAccountLists
 * @package app\api\lists\sv
 * @author Qasim
 */
class SvAccountLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['w.status', 's.takeover_mode', 'w.type', 'w.account', 'nickname','w.device_code'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['w.user_id', '=', $this->userId];
        return SvAccount::alias('w')
            ->field('w.user_id,w.id,w.device_code,w.account,w.nickname,w.avatar,w.status,w.create_time,w.extra,w.type,
             s.takeover_mode,s.open_ai,s.sort,s.remark,s.takeover_range_mode, s.takeover_type,s.robot_id')
            ->join('sv_setting s', 's.account = w.account')
            ->where($this->searchWhere)
            ->order(['s.sort' => 'desc', 'w.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                if (empty($item['takeover_mode'])) {
                    $item['takeover_mode'] = 0;
                }

                if (empty($item['robot_id'])) {
                    $item['robot_id'] = 0;
                }

                $item['robot_name'] = KbRobot::where('id', $item['robot_id'])->where('user_id', $this->userId)->value('name', '');
                // 检查 extra 是否为 JSON，如果是，则转换为数组
                if (!empty($item['extra'])) {
                    $extraArray = json_decode($item['extra'], true);
                } else {
                    $extraArray = [];
                }
                foreach ($extraArray  as $key => $v) {
                    $item[$key] = $v;
                }
                $item['status'] = 0;
                $item['business_card'] = 0;
                if ($item['type'] == 3 && isset($item['account_type']) && $item['account_type'] == 1) {
                    $item['business_card'] = SvMaterial::where('account', $item['account'])
                        ->where('user_id',$item['user_id'])
                        ->where('m_type', 5)->where('type', 3)
                        ->count();
                }

                // 请求在线状态
                $result = MessageLogic::getOnlineStatus($item['account'], $item['device_code'], $item['type']);
                if ($result) {
                    $item['status'] = MessageLogic::getReturnData();
                    $item->status = $item['status'];
                    $item->save();
                }
            })
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['w.user_id', '=', $this->userId];
        return SvAccount::alias('w')
            ->field('w.id,w.device_code,w.account,w.nickname,w.avatarunt,w.status,w.create_time,s.takeover_mode, s.takeover_type, s.robot_id')
            ->leftJoin('sv_setting s', 's.account = w.account')
            ->where($this->searchWhere)
            ->count();
    }
}
