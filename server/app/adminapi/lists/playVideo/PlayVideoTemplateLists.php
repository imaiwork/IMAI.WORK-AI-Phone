<?php

declare(strict_types=1);

namespace app\adminapi\lists\playVideo;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\service\FileService;
use think\facade\Db;

/**
 * 爆款玩法模板列表（后台只读）。
 * 数据由定时任务每小时从模板中心同步到本地表，上下架/编辑在模板中心操作，
 * 本页用于核对当前站实际生效的模板与素材落地情况。
 */
class PlayVideoTemplateLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [];
    }

    public function where(): array
    {
        $where = [];
        if (!empty($this->params['play'])) {
            $where[] = ['play', '=', (string)$this->params['play']];
        }
        if (!empty($this->params['name'])) {
            $where[] = ['name', 'like', '%' . trim((string)$this->params['name']) . '%'];
        }
        if (isset($this->params['status']) && $this->params['status'] !== '') {
            $where[] = ['status', '=', (int)$this->params['status']];
        }
        return $where;
    }

    public function lists(): array
    {
        $rows = Db::name('play_video_template')
            ->where($this->where())
            ->order('play', 'asc')
            ->order('sort', 'asc')
            ->order('create_time', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        foreach ($rows as &$row) {
            $script = json_decode((string)($row['script'] ?? '[]'), true);
            $row['script_count'] = is_array($script) ? count($script) : 0;
            $row['video_url'] = $row['video_url'] !== '' ? FileService::getFileUrl((string)$row['video_url']) : '';
            $row['cover_url'] = $row['cover_url'] !== '' ? FileService::getFileUrl((string)$row['cover_url']) : '';
            unset($row['script']);
        }
        return $rows;
    }

    public function count(): int
    {
        return Db::name('play_video_template')->where($this->where())->count();
    }
}
