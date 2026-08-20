<?php

namespace app\adminapi\lists\user;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\member\MemberUser;
use app\common\service\FileService;

class MemberUserLists extends BaseAdminDataLists
{
    public function lists(): array
    {
        $where = $this->setSearch();
        $rows = MemberUser::alias('MU')
            ->leftJoin('user U', 'MU.user_id = U.id')
            ->leftJoin('user_level UL', 'MU.level_id = UL.id')
            ->field('MU.id, MU.user_id, MU.level_id, MU.start_time, MU.end_time, MU.status,
                     MU.source, MU.source_remark, U.nickname, U.avatar, U.mobile, U.sn, UL.level_name as level_name')
            ->where($where)
            ->order('MU.id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        foreach ($rows as &$r) {
            $r['avatar'] = FileService::getFileUrl($r['avatar'] ?? '');
            $r['start_time_str'] = $r['start_time'] ? date('Y-m-d H:i', $r['start_time']) : '';
            $r['end_time_str']   = $r['end_time']   ? date('Y-m-d H:i', $r['end_time'])   : '';
        }
        return $rows;
    }

    public function count(): int
    {
        return MemberUser::alias('MU')
            ->leftJoin('user U', 'MU.user_id = U.id')
            ->where($this->setSearch())
            ->count();
    }

    public function setSearch(): array
    {
        $where = [];
        if (!empty($this->params['user_keyword'])) {
            $kw = $this->params['user_keyword'];
            $where[] = ['U.id|U.sn|U.nickname|U.mobile', 'like', "%{$kw}%"];
        }
        if (isset($this->params['level_id']) && $this->params['level_id'] !== '') {
            $where[] = ['MU.level_id', '=', (int)$this->params['level_id']];
        }
        if (isset($this->params['status']) && $this->params['status'] !== '') {
            $where[] = ['MU.status', '=', (int)$this->params['status']];
        }
        return $where;
    }
}
