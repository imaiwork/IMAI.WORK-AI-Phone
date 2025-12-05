<?php

namespace app\adminapi\lists\sv;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\service\FileService;
use app\common\model\user\UserTokensLog;
use app\common\model\ModelConfig;
use app\adminapi\logic\WorkbenchLogic;


/**
 * tokens消耗情况
 * Class AccounttokenlogLists
 * @package app\Adminapi\lists\sv
 */
class AccountTokenlogLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function lists(): array
    {
        //加载算力计费列表
        return UserTokensLog::where('user_id',$this->request->param('user_id'))
            ->where($this->searchWhere)
            ->field('id, sn,user_id, action, change_type, extra, change_amount, create_time')
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function($item){
                $item['type_name']      = ModelConfig::where('code', $item['change_type'])->value('name') ?? '充值';
                $item['extra']          = json_decode($item['extra'], true) ?? [];

                if ($item['action'] == 1) {

                    $item['extra'] = [
                        '失败恢复' => $item['change_amount']
                    ];
                }

                // 计算算力
                if (isset($item['extra']['实际消耗算力'])) {

                    $points = $item['extra']['实际消耗算力'];
                } else {

                    $points = $item['change_amount'];
                }

                $item['points'] = $points;
            })
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function count(): int
    {
        return UserTokensLog::where('user_id',$this->request->param('user_id'))
            ->where($this->searchWhere)
            ->count();
    }

    /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function setSearch(): array
    {

        return [];
    }
}
