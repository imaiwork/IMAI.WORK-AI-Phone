<?php

namespace app\adminapi\lists\recharge;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\service\FileService;
use app\common\model\user\UserTokensLog;
use app\common\model\ModelConfig;
use app\adminapi\logic\WorkbenchLogic;


/**
 * tokens消耗情况
 * Class TokensLogLists
 * @package app\Adminapi\lists\recharge
 */
class TokensLogLists extends BaseAdminDataLists implements ListsSearchInterface
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
        $tokensLists = WorkbenchLogic::tokensLists();

        return UserTokensLog::alias('l')
            ->join('user u', 'u.id = l.user_id')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('l.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('type_id'), function ($query) {
                $query->where('l.change_type', $this->request->get('type_id'));
            })
            ->field('l.id, l.user_id, l.action, l.change_type, l.extra, l.change_amount, l.create_time,u.nickname,u.avatar')
            ->order('l.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) use ($tokensLists) {
                $item['avatar']         = FileService::getFileUrl($item['avatar']);
                if (!in_array($item['change_type'],[5060,9100])){
                    $item['type_name']      = ModelConfig::where('code', $item['change_type'])->value('name') ?? '充值';
                }else{
                    $change_type = [
                        '9100'=>'数字人视频合成退费',
                        '5060'=>'数字人视频合成补扣'
                    ];
                    $item['type_name']      =  $change_type[$item['change_type']] ?? '充值';
                }
                $item['extra']          = json_decode($item['extra'], true) ?? [];
                $item['cast_unit']      = '';

                if ($item['action'] == 1) {

                    $item['extra'] = [
                        '失败恢复' => $item['change_amount']
                    ];
                }

                foreach ($tokensLists as $value) {

                    if ($value['code'] == $item['change_type']) {

                        $item['cast_unit'] = $value['cast_unit'];
                    }
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
        return UserTokensLog::alias('l')
            ->join('user u', 'u.id = l.user_id')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('l.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('type_id'), function ($query) {
                $query->where('l.change_type', $this->request->get('type_id'));
            })
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
