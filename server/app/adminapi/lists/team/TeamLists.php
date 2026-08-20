<?php

namespace app\adminapi\lists\team;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\team\Team;
use app\common\model\user\User;
use app\common\service\ConfigService;

/**
 * 团队(企业OEM)列表 —— 站长后台(对齐 company-web/admin 契约)
 * Class TeamLists
 * @package app\adminapi\lists\team
 */
class TeamLists extends BaseAdminDataLists
{
    const OEM_DESC = [0 => '免费版', 1 => '待审核', 2 => '已开通'];

    /**
     * @notes 搜索条件
     */
    public function queryWhere(): array
    {
        $where = [];
        if (isset($this->params['name']) && $this->params['name'] !== '') {
            $where[] = ['name', 'like', '%' . $this->params['name'] . '%'];
        }
        if (isset($this->params['oem_status']) && $this->params['oem_status'] !== '') {
            $where[] = ['oem_status', '=', (int)$this->params['oem_status']];
        }
        if (isset($this->params['status']) && $this->params['status'] !== '') {
            $where[] = ['status', '=', (int)$this->params['status']];
        }
        // 团队主(昵称/手机号模糊)
        $owner = trim((string)($this->params['owner'] ?? ''));
        if ($owner !== '') {
            $ownerIds = User::where(function ($q) use ($owner) {
                $q->whereLike('nickname', '%' . $owner . '%')
                    ->whereOr('mobile', 'like', '%' . $owner . '%');
            })->column('id');
            // 无匹配用户时返回空列表
            $where[] = ['owner_id', 'in', $ownerIds ?: [0]];
        }
        // 站点域名
        if (isset($this->params['domain']) && trim((string)$this->params['domain']) !== '') {
            $where[] = ['domain', 'like', '%' . trim((string)$this->params['domain']) . '%'];
        }
        // 创建时间范围(日期字符串,与全局 ListsValidate date 规则一致)
        if (!empty($this->params['start_time'])) {
            $where[] = ['create_time', '>=', (int)strtotime((string)$this->params['start_time'])];
        }
        if (!empty($this->params['end_time'])) {
            $where[] = ['create_time', '<=', (int)strtotime((string)$this->params['end_time'])];
        }
        return $where;
    }

    /**
     * @notes 列表(附团队主、站点标题、OEM状态、申请时间等)
     */
    public function lists(): array
    {
        $lists = Team::where($this->queryWhere())
            ->field('id,name,owner_id,seat_limit,member_count,domain,status,oem_status,oem_apply_time,oem_pay_tokens,create_time')
            ->limit($this->limitOffset, $this->limitLength)
            ->order(['id' => 'desc'])
            ->select()
            ->toArray();

        $ownerIds = array_values(array_filter(array_column($lists, 'owner_id')));
        $ownerMap = [];
        if ($ownerIds) {
            $owners = User::whereIn('id', $ownerIds)->field('id,nickname,mobile,tokens')->select()->toArray();
            $ownerMap = array_column($owners, null, 'id');
        }
        foreach ($lists as &$item) {
            $owner = $ownerMap[$item['owner_id']] ?? null;
            $item['owner_name']          = $owner['nickname'] ?? '';
            $item['owner_mobile']        = $owner['mobile'] ?? '';
            $item['owner_tokens']        = $owner['tokens'] ?? 0;
            $item['oem_status']          = (int)$item['oem_status'];
            $item['oem_status_desc']     = self::OEM_DESC[$item['oem_status']] ?? '未知';
            $item['status']              = (int)$item['status'];
            // 站点标题(该团队品牌配置)
            $item['site_title']          = ConfigService::get('website', 'name', '', (int)$item['id']);
            // 模型 toArray 已把 create_time 格式化成字符串,(int) 强转只剩年份会变成 1970-01-01
            $ct = $item['create_time'] ?? '';
            $item['create_time']         = is_numeric($ct) && (int)$ct > 0 ? date('Y-m-d H:i:s', (int)$ct) : (string)$ct;
            $applyTs = !empty($item['oem_apply_time']) ? (int)$item['oem_apply_time'] : 0;
            $applyFmt = $applyTs > 0 ? date('Y-m-d H:i:s', $applyTs) : '';
            $item['oem_apply_time']      = $applyFmt;
            $item['oem_apply_timestamp'] = $applyFmt;
            $item['oem_apply_time_desc'] = $applyFmt;
        }
        return $lists;
    }

    /**
     * @notes 总数
     */
    public function count(): int
    {
        return Team::where($this->queryWhere())->count();
    }
}
