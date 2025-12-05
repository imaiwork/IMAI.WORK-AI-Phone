<?php


namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvAccount;
use app\common\model\wechat\AiWechat;
use app\api\logic\sv\MessageLogic;
use think\facade\Db;

use app\common\model\kb\KbRobot;

/**
 * 列表
 * Class AllAccountLists
 * @package app\api\lists\sv
 * @author Qasim
 */
class AllAccountLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['w.status', 's.takeover_mode', 'w.type', 'w.account', 'nickname', 'w.device_code'],
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
            ->field('w.user_id,w.id,w.device_code,w.account,w.nickname,w.avatar,w.status,w.create_time,w.update_time,w.extra,w.type,
                    s.takeover_mode,s.open_ai,s.sort,s.remark,s.takeover_range_mode, s.takeover_type,s.robot_id, d.device_name,d.device_model')
            ->join('sv_device d', 'd.device_code = w.device_code', 'left')
            ->leftJoin('sv_setting s', 's.account = w.account')
            ->where($this->searchWhere)
            ->order('w.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['device_name'] = is_null($item['device_name']) ? $item['device_model'] : $item['device_name'];
                if (empty($item['takeover_mode'])) {
                    $item['takeover_mode'] = 0;
                }

                if (empty($item['robot_id'])) {
                    $item['robot_id'] = 0;
                }

                $item['robot_name'] = KbRobot::where('id', $item['robot_id'])->where('user_id', $this->userId)->value('name', '');

                if (!empty($item['extra'])) {
                    $extraArray = json_decode($item['extra'], true);
                } else {
                    $extraArray = [];
                }
                foreach ($extraArray  as $key => $v) {
                    $item[$key] = $v;
                }

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
        $this->searchWhere[] = ['w.user_id', '=', $this->userId];
        return  SvAccount::alias('w')
            ->field('w.user_id,w.id,w.device_code,w.account,w.nickname,w.avatar,w.status,w.create_time,w.update_time,w.extra,w.type,
                    s.takeover_mode,s.open_ai,s.sort,s.remark,s.takeover_range_mode, s.takeover_type,s.robot_id')
            ->join('sv_device d', 'd.device_code = w.device_code', 'left')
            ->leftJoin('sv_setting s', 's.account = w.account')
            ->where($this->searchWhere)->count();
    }
}
