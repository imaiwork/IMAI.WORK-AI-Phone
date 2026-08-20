<?php

namespace app\adminapi\lists\recharge;

use app\adminapi\lists\BaseAdminDataLists;
use app\adminapi\logic\WorkbenchLogic;
use app\common\enum\user\AccountLogEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\ModelConfig;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;

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
            // 团队空间流水标记(team_id>0);LEFT JOIN 拿团队名,团队已删仍显示标签
            ->leftJoin('team t', 't.id = l.team_id')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('l.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('type_id'), function ($query) {
                if ($this->request->get('type_id') == 9001) {
                    $query->where('l.change_type', 'in', [9001, 9102]);
                } else {
                    $query->where('l.change_type', $this->request->get('type_id'));
                }
            })
            ->field('l.id, l.user_id, l.action, l.change_type, l.extra, l.change_amount, l.status, l.create_time,u.nickname, u.mobile, u.avatar,l.remark, l.team_id, t.name as team_name')
            ->order('l.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) use ($tokensLists) {
                $item['avatar']         = FileService::getFileUrl($item['avatar']);
                $item['team_id']        = (int)($item['team_id'] ?? 0);
                $item['team_name']      = $item['team_id'] > 0 ? (string)($item['team_name'] ?? '') : '';
                // ModelConfig 优先（计费模型名），其次枚举描述，避免未配置类型误显示为「充值」
                $modelName = ModelConfig::where('code', $item['change_type'])->value('name');
                $enumName  = AccountLogEnum::getChangeTypeDesc($item['change_type']);
                $item['type_name'] = $modelName ?: ($enumName ?: ($item['remark'] ?: '未知类型'));
                $extra = json_decode((string)$item['extra'], true);
                if (!is_array($extra)) {
                    $extra = [];
                }
                $item['cast_unit']      = '';

                // status=2 为请求失败回滚加回；充值/赠送等正常增加不应标「失败恢复」
                // 注意：模型 ArrayAccess 不能间接改嵌套元素，需整体回写
                if ((int)$item['action'] === AccountLogEnum::INC && (int)($item['status'] ?? 1) === 2) {
                    $extra['失败恢复'] = $item['change_amount'];
                }
                $item['extra'] = $extra;

                foreach ($tokensLists as $value) {

                    if ($value['code'] == $item['change_type']) {

                        $item['cast_unit'] = $value['cast_unit'];
                    }
                }

                // 计算算力
                if (isset($extra['实际消耗算力']) && !in_array($item['change_type'],[5035,5034,5033,5032])) {

                    $points = $extra['实际消耗算力'];
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
