<?php

namespace app\adminapi\lists\shanjian;

use app\adminapi\lists\BaseAdminDataLists;
use app\api\logic\shanjian\ShanjianAnchorLogic;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\model\user\User;
use app\common\service\digitalHuman\PendingAnchorTaskService;
use app\common\service\FileService;

class ShanjianAnchorLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '%like%' => ['sj.name','u.nickname'],
            'in' => ['sj.status'],
        ];
    }

    public function lists(): array
    {
        $list = ShanjianAnchorLogic::applyCloneModeFilter(
            ShanjianAnchor::alias('sj')
                ->join('user u', 'u.id = sj.user_id')
                ->where($this->searchWhere)
                ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                    $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
                })
                ->field('sj.*,u.nickname'),
            'sj'
        )
            ->order(['sj.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        // 系统默认形象存的是本地静态相对路径(static/videos/...)，展示时补全域名
        foreach ($list as &$item) {
            $item['anchor_url'] = \app\common\service\FileService::getFileUrl((string)($item['anchor_url'] ?? ''));
            $item['authorized_url'] = \app\common\service\FileService::getFileUrl((string)($item['authorized_url'] ?? ''));
        }
        unset($item);

        // 公共形象任务提交后到分发前的排队记录，前置到第一页，避免后台几分钟内看不到刚提交的任务
        if ($this->limitOffset === 0) {
            $list = array_merge($this->pendingRows(), $list);
        }
        return $list;
    }

    /**
     * 已提交但尚未分发到闪剪渠道的公共形象任务虚拟行(id 为负数、status=1 前端显示"形象合成中")
     */
    protected function pendingRows(): array
    {
        // 传状态筛选时不注入(虚拟行固定为"形象合成中")；参数为数组等异常形态时同样不注入
        $status   = $this->request->get('status', '');
        $name     = $this->request->get('name', '');
        $nickname = $this->request->get('nickname', '');
        if (is_array($name) || is_array($nickname)) {
            return [];
        }
        $hasStatusFilter = is_array($status) ? !empty($status) : trim((string)$status) !== '';
        if ($hasStatusFilter) {
            return [];
        }

        $name      = trim((string)$name);
        $nickname  = trim((string)$nickname);
        $startTime = $this->request->get('start_time');
        $endTime   = $this->request->get('end_time');

        $rows = PendingAnchorTaskService::pendingQuery('shanjian')
            ->when($name !== '', function ($query) use ($name) {
                $query->where('dh.name', 'like', '%' . $name . '%');
            })
            ->when($nickname !== '', function ($query) use ($nickname) {
                $query->where('u.nickname', 'like', '%' . $nickname . '%');
            })
            ->when($startTime && $endTime, function ($query) use ($startTime, $endTime) {
                $query->whereBetween('dh.create_time', [strtotime($startTime), strtotime($endTime)]);
            })
            ->order('dh.id', 'desc')
            ->select()
            ->toArray();

        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'id'             => -(int)$row['id'], // 负数=排队任务虚拟行，非 shanjian_anchor 主键
                'user_id'        => $row['user_id'],
                'name'           => $row['name'],
                'task_id'        => '',
                'status'         => 1,
                'pic'            => FileService::getFileUrl((string)$row['image']),
                'anchor_id'      => '',
                'voice_id'       => '',
                'voice_model'    => '',
                'voice_url'      => '',
                'remark'         => PendingAnchorTaskService::REMARK,
                'token'          => '',
                'anchor_url'     => FileService::getFileUrl((string)$row['result_url']),
                'authorized_pic' => FileService::getFileUrl((string)$row['authorized_pic']),
                'authorized_url' => FileService::getFileUrl((string)$row['authorized_url']),
                'create_time'    => $row['create_time'],
                'update_time'    => $row['update_time'],
                'dh_id'          => (int)$row['id'],
                'clone_type'     => (int)$row['clone_mode'] === 3
                    ? ShanjianAnchorLogic::CLONE_TYPE_PRO
                    : ShanjianAnchorLogic::CLONE_TYPE_FAST,
                'nickname'       => $row['nickname'],
                'is_pending'     => 1,
            ];
        }
        return $list;
    }

    public function count(): int
    {
        return ShanjianAnchorLogic::applyCloneModeFilter(
            ShanjianAnchor::alias('sj')
                ->join('user u', 'u.id = sj.user_id')
                ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                    $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
                })
                ->where($this->searchWhere),
            'sj'
        )->count() + count($this->pendingRows());
    }
}
