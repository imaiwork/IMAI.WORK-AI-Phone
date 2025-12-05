<?php


namespace app\api\lists\tools;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\tools\Tools;
use app\common\model\tools\ToolsLog;
use app\common\model\user\User;


/**
 * 充值记录列表
 * Class RechargeLists
 * @package app\api\lists\recharge
 */
class
ToolsLogLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "=" => ['tools_id', 'draw'],
        ];
    }

    /**
     * @notes 获取工具列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list                = ToolsLog::where($this->searchWhere)
            ->order(['id' => "desc"])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->map(function ($data) {
                $data['user_avatar'] = User::where('id', $data['user_id'])->value('avatar', "");
                $data['char_avatar'] = "";
                return $data;
            })->toArray();
        return $list;
        $logList = [];
        foreach ($list as $v) {
            $logList[] = [
                'avatar'      => $v['user_avatar'],
                'ask'         => $v['origin_ask'],
                'create_time' => $v['create_time']
            ];

            $logList[] = [
                'avatar'      => $v['char_avatar'],
                'reply'       => $v['reply'],
                'create_time' => $v['create_time']
            ];
        }
        return $logList;
    }


    /**
     * @notes  获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return ToolsLog::where($this->searchWhere)->count();
    }
}
