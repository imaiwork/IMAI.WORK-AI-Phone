<?php

declare(strict_types=1);

namespace app\adminapi\lists\draw;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\draw\DrawEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\draw\DrawAsset;
use app\common\model\draw\DrawTask;
use app\common\service\FileService;

/**
 * draw 生图/生视频任务记录列表（读新表 la_draw_task）
 */
class DrawRecordLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['t.media_type', 't.status'],
        ];
    }

    public function lists(): array
    {
        return DrawTask::alias('t')
            ->leftJoin('user u', 'u.id = t.user_id and t.user_id <> 0')
            ->where($this->searchWhere)
            ->when($this->request->get('media_type'), function ($query) {
                $query->where('t.media_type', $this->request->get('media_type'));
            })
            ->when($this->request->get('status') !== null && $this->request->get('status') !== '', function ($query) {
                $query->where('t.status', (int)$this->request->get('status'));
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('t.create_time', [
                    strtotime($this->request->get('start_time')),
                    strtotime($this->request->get('end_time')),
                ]);
            })
            ->field('t.*,u.nickname,u.avatar')
            ->order('t.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['avatar'] = $item['avatar'] ? FileService::getFileUrl($item['avatar']) : '';
                $item['status_text'] = self::statusText((int)$item['status']);
                $item['assets'] = self::formatAssets((int)$item['id']);
            })
            ->toArray();
    }

    public function count(): int
    {
        return DrawTask::alias('t')
            ->leftJoin('user u', 'u.id = t.user_id and t.user_id <> 0')
            ->where($this->searchWhere)
            ->when($this->request->get('media_type'), function ($query) {
                $query->where('t.media_type', $this->request->get('media_type'));
            })
            ->when($this->request->get('status') !== null && $this->request->get('status') !== '', function ($query) {
                $query->where('t.status', (int)$this->request->get('status'));
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('t.create_time', [
                    strtotime($this->request->get('start_time')),
                    strtotime($this->request->get('end_time')),
                ]);
            })
            ->count();
    }

    public static function formatAssets(int $taskId): array
    {
        $assets = DrawAsset::where('task_id', $taskId)
            ->order('asset_type', 'asc')
            ->order('sort', 'asc')
            ->select()
            ->toArray();
        foreach ($assets as &$asset) {
            $asset['file_full_url'] = $asset['file_url']
                ? FileService::getFileUrl($asset['file_url'])
                : '';
        }
        unset($asset);
        return $assets;
    }

    public static function statusText(int $status): string
    {
        return [
            DrawEnum::STATUS_PENDING    => '待处理',
            DrawEnum::STATUS_SUBMITTED  => '已提交',
            DrawEnum::STATUS_PROCESSING => '生成中',
            DrawEnum::STATUS_SUCCESS    => '成功',
            DrawEnum::STATUS_FAILED     => '失败',
            DrawEnum::STATUS_CANCELLED  => '已取消',
        ][$status] ?? '未知';
    }
}
