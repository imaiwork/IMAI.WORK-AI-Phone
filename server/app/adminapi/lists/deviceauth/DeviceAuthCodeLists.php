<?php

namespace app\adminapi\lists\deviceauth;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\deviceauth\DeviceAuthCodeEnum;
use app\common\lists\ListsExcelInterface;
use app\common\lists\ListsExtendInterface;
use app\common\model\deviceauth\DeviceCdkCode;
use think\db\Query;

class DeviceAuthCodeLists extends BaseAdminDataLists implements ListsExcelInterface, ListsExtendInterface
{
    public function setSearch(bool $ignoreStatus = false): array
    {
        $where = [];
        if (isset($this->params['type']) && $this->params['type'] !== '') {
            $where[] = ['c.type', '=', $this->params['type']];
        }
        if (!$ignoreStatus && isset($this->params['status']) && $this->params['status'] !== '') {
            $where[] = ['c.status', '=', $this->params['status']];
        }
        if (isset($this->params['code']) && $this->params['code'] !== '') {
            $where[] = ['c.code', 'like', '%' . $this->params['code'] . '%'];
        }
        if (isset($this->params['keyword']) && $this->params['keyword'] !== '') {
            $where[] = ['c.code|c.device_code|c.middle_device_code', 'like', '%' . $this->params['keyword'] . '%'];
        }
        // 按拥有者用户ID精确筛选（用户详情「CDK记录」）
        if (isset($this->params['user_id']) && $this->params['user_id'] !== '') {
            $where[] = ['c.owner_user_id', '=', (int)$this->params['user_id']];
        }
        if (isset($this->params['start_time']) && $this->params['start_time']) {
            $where[] = ['c.create_time', '>=', strtotime($this->params['start_time'])];
        }
        if (isset($this->params['end_time']) && $this->params['end_time']) {
            $where[] = ['c.create_time', '<=', strtotime($this->params['end_time'])];
        }
        return $where;
    }

    /**
     * 构建列表查询：同时关联使用人、拥有者，避免未使用CDK（user_id=0）被用户关键词漏掉
     */
    private function buildQuery(bool $ignoreStatus = false): Query
    {
        $query = DeviceCdkCode::alias('c')
            ->leftJoin('user u', 'u.id = c.user_id')
            ->leftJoin('user ou', 'ou.id = c.owner_user_id')
            ->where($this->setSearch($ignoreStatus));

        if (isset($this->params['user_keyword']) && $this->params['user_keyword'] !== '') {
            $keyword = '%' . $this->params['user_keyword'] . '%';
            $query->where(function (Query $q) use ($keyword) {
                $q->where('u.nickname|u.mobile', 'like', $keyword)
                    ->whereOr('ou.nickname|ou.mobile', 'like', $keyword);
            });
        }

        return $query;
    }

    public function extend(): array
    {
        $rows = $this->buildQuery(true)
            ->field('c.status,COUNT(*) AS total')
            ->group('c.status')
            ->select()
            ->toArray();

        $statusCount = [
            DeviceAuthCodeEnum::STATUS_UNUSED   => 0,
            DeviceAuthCodeEnum::STATUS_USED     => 0,
            DeviceAuthCodeEnum::STATUS_DISABLED => 0,
        ];
        foreach ($rows as $row) {
            $statusCount[(int)$row['status']] = (int)$row['total'];
        }

        return [
            'unused'   => $statusCount[DeviceAuthCodeEnum::STATUS_UNUSED],
            'used'     => $statusCount[DeviceAuthCodeEnum::STATUS_USED],
        ];
    }

    public function lists(): array
    {
        $lists = $this->buildQuery()
            ->field('c.id,c.code,c.type,c.status,c.source,c.device_code,c.create_time,c.use_time,u.nickname,c.owner_user_id,ou.nickname as owner_user_name')
            ->limit($this->limitOffset, $this->limitLength)
            ->order('c.id desc')
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['type_desc'] = DeviceAuthCodeEnum::getTypeDesc($item['type']);
            $item['status_desc'] = DeviceAuthCodeEnum::getStatusDesc($item['status']);
            $item['source_desc'] = DeviceAuthCodeEnum::getSourceDesc($item['source']);
            $item['create_time'] = format_datetime($item['create_time'] ?? '');
            $item['use_time'] = format_datetime($item['use_time'] ?? '');
        }
        unset($item);
        return $lists;
    }

    public function count(): int
    {
        return $this->buildQuery()->count();
    }

    public function setExcelFields(): array
    {
        return [
            'code'        => '设备CDK',
            'type_desc'   => '类型',
            'status_desc' => '状态',
            'create_time' => '入库时间',
            'nickname'    => '使用用户',
            'device_code' => '使用设备号',
        ];
    }

    public function setFileName(): string
    {
        return '设备CDK';
    }
}
