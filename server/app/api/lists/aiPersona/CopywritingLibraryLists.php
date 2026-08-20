<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\AiPersonaCopywritingLibrary;
use app\common\model\aiPersona\AiPersonaCopywritingLibraryPlatformUse;

class CopywritingLibraryLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['persona_id', 'library_type', 'driver_type', 'status'],
        ];
    }

    public function lists(): array
    {
        $where = $this->searchWhere;
        $where[] = ['user_id', '=', $this->userId];
        $keyword = trim((string)($this->params['keyword'] ?? ''));
        $platform = (int)($this->params['platform'] ?? 0);

        $list = AiPersonaCopywritingLibrary::where($where)
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->whereLike('title', '%' . $keyword . '%')
                        ->whereOr('content', 'like', '%' . $keyword . '%')
                        ->whereOr('topic', 'like', '%' . $keyword . '%');
                });
            })
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $platformUseMap = [];
        $anyPlatformUsedMap = [];
        if (!empty($list)) {
            $libraryIds = array_column($list, 'id');
            $platformQuery = AiPersonaCopywritingLibraryPlatformUse::whereIn('library_id', $libraryIds)
                ->where('use_count', '>', 0);
            if ($platform > 0) {
                $platformQuery->where('platform', $platform);
            }
            $platformRows = $platformQuery->field('library_id,platform,use_count')->select()->toArray();
            foreach ($platformRows as $row) {
                $libraryId = (int)$row['library_id'];
                $anyPlatformUsedMap[$libraryId] = 1;
                if ($platform > 0) {
                    $platformUseMap[$libraryId] = (int)$row['use_count'];
                }
            }
        }

        foreach ($list as &$item) {
            if ((int)($item['library_type'] ?? 0) === AiPersonaCopywritingLibrary::LIBRARY_TYPE_PUBLISH) {
                if ($platform > 0) {
                    $platformUseCount = (int)($platformUseMap[(int)$item['id']] ?? 0);
                    $item['platform_use_count'] = $platformUseCount;
                    $item['is_used'] = $platformUseCount > 0 ? 1 : 0;
                } else {
                    $item['is_used'] = !empty($anyPlatformUsedMap[(int)$item['id']]) ? 1 : 0;
                }
            } else {
                $item['is_used'] = (int)($item['use_count'] ?? 0) > 0 ? 1 : 0;
            }
        }
        unset($item);

        return $list;
    }

    public function count(): int
    {
        $where = $this->searchWhere;
        $where[] = ['user_id', '=', $this->userId];
        $keyword = trim((string)($this->params['keyword'] ?? ''));

        return AiPersonaCopywritingLibrary::where($where)
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->whereLike('title', '%' . $keyword . '%')
                        ->whereOr('content', 'like', '%' . $keyword . '%')
                        ->whereOr('topic', 'like', '%' . $keyword . '%');
                });
            })
            ->count();
    }

    public function extend(): array
    {
        $where = [['user_id', '=', $this->userId]];
        if (!empty($this->params['persona_id'])) {
            $where[] = ['persona_id', '=', (int)$this->params['persona_id']];
        }

        $rows = AiPersonaCopywritingLibrary::where($where)
            ->field('library_type,driver_type,count(*) as count')
            ->group('library_type,driver_type')
            ->select()
            ->toArray();

        $tabs = [
            'video_driver' => [
                'total' => 0,
                'news' => 0,
                'oral' => 0,
                'material_mixcut' => 0,
            ],
            'publish' => 0,
        ];

        foreach ($rows as $row) {
            $count = (int)($row['count'] ?? 0);
            if ((int)$row['library_type'] === AiPersonaCopywritingLibrary::LIBRARY_TYPE_PUBLISH) {
                $tabs['publish'] += $count;
                continue;
            }

            $tabs['video_driver']['total'] += $count;
            switch ((int)$row['driver_type']) {
                case AiPersonaCopywritingLibrary::DRIVER_TYPE_NEWS:
                    $tabs['video_driver']['news'] += $count;
                    break;
                case AiPersonaCopywritingLibrary::DRIVER_TYPE_ORAL:
                    $tabs['video_driver']['oral'] += $count;
                    break;
                case AiPersonaCopywritingLibrary::DRIVER_TYPE_MATERIAL_MIXCUT:
                    $tabs['video_driver']['material_mixcut'] += $count;
                    break;
            }
        }

        return ['tabs' => $tabs];
    }
}
