<?php

namespace app\common\service\square;

use app\common\model\aiPersona\Material;
use app\common\model\file\File;
use app\common\model\square\SquareAsset;
use app\common\model\square\SquareAssetGroup;
use app\common\model\square\SquareAssetRef;
use app\common\service\ffmpeg\MaterialService;
use app\common\service\FileService;
use think\facade\Db;

class SquareAssetService
{
    public const KIND_VIDEO = SquareAsset::KIND_VIDEO;
    public const KIND_IMAGE = SquareAsset::KIND_IMAGE;
    public const MAX_REFS = 200;
    public const GROUP_NAME_MAX = 16;
    public const DESC_MAX = 60;
    public const NAME_MAX = 24;
    public const TAG_MAX = 10;
    public const TAG_LIMIT = 6;
    public const LIST_ID_PREFIX = 'sq-';
    /** 混剪素材列表用的数字 id，避免和人设自有素材主键撞车 */
    public const CLIP_ID_OFFSET = 1000000000;

    public static function listGroups(bool $onlyEnabled = false): array
    {
        $query = SquareAssetGroup::order(['sort' => 'asc', 'id' => 'asc']);
        if ($onlyEnabled) {
            $query->where('enabled', 1);
        }
        $groups = $query->select()->toArray();
        if (!$groups) {
            return [];
        }

        $groupIds = array_column($groups, 'id');
        $counts = SquareAsset::whereIn('group_id', $groupIds)
            ->where('enabled', 1)
            ->group('group_id')
            ->column('count(*)', 'group_id');

        foreach ($groups as &$group) {
            $group['asset_count'] = (int)($counts[$group['id']] ?? 0);
            $group['enabled'] = (int)$group['enabled'];
            $group['sort'] = (int)$group['sort'];
        }
        unset($group);
        return $groups;
    }

    public static function createGroup(array $raw): array
    {
        $data = self::cleanGroup($raw);
        $row = SquareAssetGroup::create($data);
        return self::formatGroup($row->toArray(), 0);
    }

    public static function updateGroup(int $id, array $raw): array
    {
        $row = SquareAssetGroup::findOrEmpty($id);
        if ($row->isEmpty()) {
            throw new \RuntimeException('分组不存在');
        }
        $data = self::cleanGroup($raw, $row->toArray());
        $row->save($data);
        $count = (int)SquareAsset::where('group_id', $id)->where('enabled', 1)->count();
        return self::formatGroup($row->refresh()->toArray(), $count);
    }

    public static function deleteGroup(int $id): void
    {
        $row = SquareAssetGroup::findOrEmpty($id);
        if ($row->isEmpty()) {
            throw new \RuntimeException('分组不存在');
        }
        Db::transaction(function () use ($row, $id) {
            SquareAsset::where('group_id', $id)->select()->delete();
            $row->delete();
        });
    }

    public static function listAssets(?int $groupId, ?int $kind, bool $onlyEnabled = false): array
    {
        $query = SquareAsset::order(['sort' => 'asc', 'id' => 'desc']);
        if ($groupId) {
            $query->where('group_id', $groupId);
        }
        if ($kind) {
            $query->where('kind', $kind);
        }

        $liveGroupIds = [];
        if ($onlyEnabled) {
            $liveGroupIds = SquareAssetGroup::where('enabled', 1)->column('id');
            if (!$liveGroupIds) {
                return [];
            }
            $query->where('enabled', 1)->whereIn('group_id', $liveGroupIds);
        }

        $assets = $query->select()->toArray();
        if (!$assets) {
            return [];
        }

        $groupNames = SquareAssetGroup::whereIn('id', array_unique(array_column($assets, 'group_id')))
            ->column('name', 'id');
        return array_map(fn ($asset) => self::formatAsset($asset, $groupNames[$asset['group_id']] ?? ''), $assets);
    }

    public static function countAllAssets(): int
    {
        return (int)SquareAsset::count();
    }

    public static function listAssetsPaged(?int $groupId, ?int $kind, int $pageNo, int $pageSize, bool $onlyEnabled = false): array
    {
        $pageNo = max(1, $pageNo);
        $pageSize = max(1, min(100, $pageSize));

        $query = SquareAsset::order(['sort' => 'asc', 'id' => 'desc']);
        if ($groupId) {
            $query->where('group_id', $groupId);
        }
        if ($kind) {
            $query->where('kind', $kind);
        }
        if ($onlyEnabled) {
            $liveGroupIds = SquareAssetGroup::where('enabled', 1)->column('id');
            if (!$liveGroupIds) {
                return ['count' => 0, 'lists' => []];
            }
            $query->where('enabled', 1)->whereIn('group_id', $liveGroupIds);
        }

        $paginator = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $pageNo,
        ])->toArray();
        $assets = $paginator['data'] ?? [];
        if (!$assets) {
            return ['count' => (int)($paginator['total'] ?? 0), 'lists' => []];
        }

        $groupNames = SquareAssetGroup::whereIn('id', array_unique(array_column($assets, 'group_id')))
            ->column('name', 'id');
        return [
            'count' => (int)($paginator['total'] ?? 0),
            'lists' => array_map(fn ($asset) => self::formatAsset($asset, $groupNames[$asset['group_id']] ?? ''), $assets),
        ];
    }

    public static function createAsset(array $raw): array
    {
        $data = self::cleanAsset($raw);
        $row = SquareAsset::create($data);
        $groupName = (string)SquareAssetGroup::where('id', $row->group_id)->value('name');
        return self::formatAsset($row->toArray(), $groupName);
    }

    public static function createAssets(int $groupId, array $items): array
    {
        if (!$items) {
            throw new \InvalidArgumentException('没有选中任何素材');
        }
        $created = [];
        Db::transaction(function () use ($groupId, $items, &$created) {
            foreach ($items as $item) {
                $item = is_array($item) ? $item : [];
                $item['group_id'] = $groupId;
                $created[] = self::createAsset($item);
            }
        });
        return $created;
    }

    public static function updateAsset(int $id, array $raw): array
    {
        $row = SquareAsset::findOrEmpty($id);
        if ($row->isEmpty()) {
            throw new \RuntimeException('素材不存在');
        }
        $data = self::cleanAsset($raw, $row->toArray());
        $row->save($data);
        $groupName = (string)SquareAssetGroup::where('id', $row->group_id)->value('name');
        return self::formatAsset($row->refresh()->toArray(), $groupName);
    }

    public static function deleteAsset(int $id): void
    {
        $row = SquareAsset::findOrEmpty($id);
        if ($row->isEmpty()) {
            throw new \RuntimeException('素材不存在');
        }
        $row->delete();
    }

    public static function browse(?int $groupId = null, ?int $kind = null, int $pageNo = 1, int $pageSize = 20): array
    {
        $groups = array_values(array_filter(
            self::listGroups(true),
            fn ($group) => (int)$group['asset_count'] > 0
        ));
        $paged = self::listAssetsPaged($groupId, $kind, $pageNo, $pageSize, true);
        return [
            'groups' => $groups,
            'assets' => $paged['lists'],
            'lists' => $paged['lists'],
            'count' => $paged['count'],
        ];
    }

    public static function listRefs(int $userId, int $personaId): array
    {
        $refs = SquareAssetRef::where('user_id', $userId)
            ->where('persona_id', $personaId)
            ->order(['id' => 'desc'])
            ->select()
            ->toArray();

        $assetIds = array_column($refs, 'asset_id');
        $assets = $assetIds
            ? SquareAsset::whereIn('id', $assetIds)->select()->toArray()
            : [];
        $assetMap = [];
        foreach ($assets as $asset) {
            $assetMap[(int)$asset['id']] = $asset;
        }
        $groupMap = $assets
            ? SquareAssetGroup::whereIn('id', array_unique(array_column($assets, 'group_id')))
                ->column('*', 'id')
            : [];

        $items = [];
        $dead = 0;
        foreach ($refs as $ref) {
            $asset = $assetMap[(int)$ref['asset_id']] ?? null;
            $group = $asset ? ($groupMap[(int)$asset['group_id']] ?? null) : null;
            $alive = $asset
                && (int)$asset['enabled'] === 1
                && $group
                && (int)$group['enabled'] === 1;
            if ($alive) {
                $items[] = self::formatLiveRef($asset, (string)$group['name'], (int)$ref['ref_at']);
            } else {
                $dead++;
                $items[] = self::formatDeadRef($ref);
            }
        }

        return [
            'items' => $items,
            'total' => count($items),
            'dead' => $dead,
            'max' => self::MAX_REFS,
        ];
    }

    public static function addRefs(int $userId, int $personaId, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            throw new \InvalidArgumentException('没有选中任何素材');
        }

        $liveGroupIds = SquareAssetGroup::where('enabled', 1)->column('id');
        $candidates = $liveGroupIds
            ? SquareAsset::where('enabled', 1)->whereIn('group_id', $liveGroupIds)->whereIn('id', $ids)->select()->toArray()
            : [];
        $candidateMap = [];
        foreach ($candidates as $asset) {
            $candidateMap[(int)$asset['id']] = $asset;
        }
        $groupNames = $candidates
            ? SquareAssetGroup::whereIn('id', array_unique(array_column($candidates, 'group_id')))->column('name', 'id')
            : [];

        $have = SquareAssetRef::where('user_id', $userId)
            ->where('persona_id', $personaId)
            ->column('asset_id');
        $haveSet = array_fill_keys(array_map('intval', $have), true);
        $total = count($have);

        $added = 0;
        $skipped = 0;
        $missing = 0;
        $capped = 0;
        $now = time();

        foreach ($ids as $id) {
            if (isset($haveSet[$id])) {
                $skipped++;
                continue;
            }
            if (!isset($candidateMap[$id])) {
                $missing++;
                continue;
            }
            if ($total >= self::MAX_REFS) {
                $capped++;
                continue;
            }
            $asset = $candidateMap[$id];
            SquareAssetRef::create([
                'user_id' => $userId,
                'persona_id' => $personaId,
                'asset_id' => $id,
                'name' => self::clip((string)$asset['name'], self::NAME_MAX) ?: '未命名素材',
                'kind' => (int)$asset['kind'],
                'group_name' => (string)($groupNames[$asset['group_id']] ?? ''),
                'ref_at' => $now,
            ]);
            $haveSet[$id] = true;
            $added++;
            $total++;
        }

        $parts = ['已加入 ' . $added . ' 个广场素材'];
        if ($skipped) {
            $parts[] = $skipped . ' 个之前已加过';
        }
        if ($missing) {
            $parts[] = $missing . ' 个已下架未加入';
        }
        if ($capped) {
            $parts[] = $capped . ' 个未加入（已达引用上限 ' . self::MAX_REFS . ' 个）';
        }

        return [
            'added' => $added,
            'missing' => $missing,
            'skipped' => $skipped,
            'capped' => $capped,
            'total' => $total,
            'message' => implode('，', $parts),
        ];
    }

    public static function removeRefs(int $userId, int $personaId, array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return 0;
        }
        return (int)SquareAssetRef::where('user_id', $userId)
            ->where('persona_id', $personaId)
            ->whereIn('asset_id', $ids)
            ->delete();
    }

    public static function resolveForGeneration(int $userId, int $personaId, array $groupIds = []): array
    {
        $listed = self::listRefs($userId, $personaId);
        $items = array_values(array_filter($listed['items'], fn ($item) => !empty($item['alive'])));
        $groupIds = array_values(array_unique(array_filter(array_map('intval', $groupIds))));
        if ($groupIds) {
            $items = array_values(array_filter(
                $items,
                fn ($item) => in_array((int)($item['group_id'] ?? 0), $groupIds, true)
            ));
        }
        return $items;
    }

    public static function clipId(int $assetId): int
    {
        return $assetId > 0 ? self::CLIP_ID_OFFSET + $assetId : 0;
    }

    public static function isClipId(int $id): bool
    {
        return $id > self::CLIP_ID_OFFSET;
    }

    public static function assetIdFromClipId(int $id): int
    {
        return self::isClipId($id) ? $id - self::CLIP_ID_OFFSET : 0;
    }

    /**
     * @param list<int|string> $ids
     * @return array{0: list<int>, 1: list<int>} [自有素材id, 广场资产id]
     */
    public static function splitClipIds(array $ids): array
    {
        $own = [];
        $square = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if (self::isClipId($id)) {
                $assetId = self::assetIdFromClipId($id);
                if ($assetId > 0 && !in_array($assetId, $square, true)) {
                    $square[] = $assetId;
                }
            } elseif ($id > 0 && !in_array($id, $own, true)) {
                $own[] = $id;
            }
        }
        return [$own, $square];
    }

    /**
     * 用户已加入当前人设的广场素材，按人设素材库口径返回。
     *
     * @return list<array<string, mixed>>
     */
    public static function libraryRows(int $userId, int $personaId, ?array $assetIds = null, bool $forSynthesis = false): array
    {
        $items = self::resolveForGeneration($userId, $personaId);
        if ($assetIds !== null) {
            $want = array_fill_keys(array_map('intval', $assetIds), true);
            $items = array_values(array_filter(
                $items,
                fn ($item) => isset($want[(int)($item['id'] ?? 0)])
            ));
        }
        $rows = [];
        foreach ($items as $item) {
            $url = (string)($item['url'] ?? $item['file_url'] ?? '');
            if ($url === '') {
                continue;
            }
            if ($forSynthesis) {
                $url = self::readySynthesisUrl($url);
                if ($url === '') {
                    continue;
                }
            }
            $rows[] = [
                'id' => (int)$item['id'],
                'persona_id' => $personaId,
                'user_id' => $userId,
                'material_name' => (string)($item['name'] ?? $item['material_name'] ?? ''),
                'material_type' => (int)($item['kind'] ?? $item['material_type'] ?? 0),
                'file_url' => $url,
                'thumbnail_url' => (string)($item['cover_url'] ?? $item['thumbnail_url'] ?? ''),
                'duration' => (int)($item['duration_sec'] ?? $item['duration'] ?? 0),
                'source' => 'square',
            ];
        }
        return $rows;
    }

    /**
     * 混剪素材卡：数字 id 带偏移，封面优先用截帧。
     *
     * @return list<array{id:int,name:string,img:string,material_type:int,duration:int,source:string}>
     */
    public static function clipCards(int $userId, int $personaId, ?array $assetIds = null): array
    {
        $out = [];
        foreach (self::libraryRows($userId, $personaId, $assetIds) as $row) {
            $clipId = self::clipId((int)$row['id']);
            $name = trim((string)$row['material_name']);
            if ($name === '') {
                $name = '素材' . $clipId;
            }
            $img = trim((string)$row['thumbnail_url']);
            if ($img === '' && (int)$row['material_type'] === self::KIND_IMAGE) {
                $img = trim((string)$row['file_url']);
            }
            $out[] = [
                'id' => $clipId,
                'name' => $name,
                'img' => $img !== '' ? FileService::getFileUrl($img) : '',
                'material_type' => (int)$row['material_type'],
                'duration' => (int)$row['duration'],
                'source' => 'square',
            ];
        }
        return $out;
    }

    /**
     * @param list<int|string> $clipIds
     * @return list<array<string, mixed>>
     */
    public static function clipRowsForSynthesis(int $userId, int $personaId, array $clipIds): array
    {
        [, $assetIds] = self::splitClipIds($clipIds);
        if (!$assetIds) {
            return [];
        }
        $rows = [];
        foreach (self::libraryRows($userId, $personaId, $assetIds, true) as $row) {
            $row['id'] = self::clipId((int)$row['id']);
            $row['material_store'] = 'square';
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * 上架中的广场素材，不依赖客户是否手动加入引用。
     * $groupIds 空 = 全部已上架分组。
     *
     * @return list<array<string, mixed>>
     */
    public static function publicLibraryRows(int $userId, int $personaId, array $groupIds = []): array
    {
        $assets = self::listAssets(null, null, true);
        $groupIds = array_values(array_unique(array_filter(array_map('intval', $groupIds))));
        if ($groupIds) {
            $assets = array_values(array_filter(
                $assets,
                fn ($asset) => in_array((int)($asset['group_id'] ?? 0), $groupIds, true)
            ));
        }
        $rows = [];
        foreach ($assets as $asset) {
            $url = self::readySynthesisUrl((string)($asset['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            $rows[] = [
                'id' => (int)$asset['id'],
                'persona_id' => $personaId,
                'user_id' => $userId,
                'material_name' => (string)($asset['name'] ?? ''),
                'material_type' => (int)($asset['kind'] ?? 0),
                'file_url' => $url,
                'thumbnail_url' => (string)($asset['cover_url'] ?? ''),
                'duration' => (int)($asset['duration_sec'] ?? 0),
                'source' => 'square',
            ];
        }
        return $rows;
    }

    public static function publicGrouped(int $userId, int $personaId, array $groupIds = []): array
    {
        $grouped = ['videos' => [], 'images' => []];
        foreach (self::publicLibraryRows($userId, $personaId, $groupIds) as $row) {
            if ((int)$row['material_type'] === self::KIND_VIDEO) {
                $grouped['videos'][] = $row;
            } else {
                $grouped['images'][] = $row;
            }
        }
        return $grouped;
    }

    /**
     * @return array{0: array{videos: list<array>, images: list<array>}, 1: array{videos: list<array>, images: list<array>}}
     */
    public static function splitOwnAndSquare(array $grouped): array
    {
        $own = ['videos' => [], 'images' => []];
        $square = ['videos' => [], 'images' => []];
        foreach (['videos', 'images'] as $key) {
            foreach ($grouped[$key] ?? [] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (($item['source'] ?? '') === 'square') {
                    $square[$key][] = $item;
                } else {
                    $own[$key][] = $item;
                }
            }
        }
        return [$own, $square];
    }

    /**
     * 两边对半取，不够再用另一边补缺口。
     *
     * @param list<array<string, mixed>> $own
     * @param list<array<string, mixed>> $square
     * @return list<array<string, mixed>>
     */
    public static function pickHalfAndFill(array $own, array $square, int $need): array
    {
        $need = max(0, $need);
        if ($need === 0) {
            return [];
        }
        shuffle($own);
        shuffle($square);
        $half = intdiv($need, 2);
        $pickedOwn = array_slice($own, 0, $half);
        $pickedSquare = array_slice($square, 0, $half);
        $ownUsed = count($pickedOwn);
        $squareUsed = count($pickedSquare);
        $gap = $need - $ownUsed - $squareUsed;
        if ($gap > 0) {
            $fromOwn = array_slice($own, $ownUsed, $gap);
            $pickedOwn = array_merge($pickedOwn, $fromOwn);
            $gap -= count($fromOwn);
            if ($gap > 0) {
                $pickedSquare = array_merge($pickedSquare, array_slice($square, $squareUsed, $gap));
            }
        }
        return array_merge($pickedOwn, $pickedSquare);
    }

    public static function selectLibraryAndSquare(
        array $ownGrouped,
        int $userId,
        int $personaId,
        array $groupIds,
        int $needV,
        int $needI
    ): array {
        [$own] = self::splitOwnAndSquare($ownGrouped);
        $square = self::publicGrouped($userId, $personaId, $groupIds);
        return array_merge(
            self::pickHalfAndFill($own['videos'], $square['videos'], $needV),
            self::pickHalfAndFill($own['images'], $square['images'], $needI)
        );
    }

    public static function appendLibraryMaterials(array $grouped, int $userId, int $personaId, array $groupIds = []): array
    {
        $grouped['videos'] = $grouped['videos'] ?? [];
        $grouped['images'] = $grouped['images'] ?? [];
        $haveUrls = [];
        foreach (array_merge($grouped['videos'], $grouped['images']) as $item) {
            $url = Material::rawFileUrl($item);
            if ($url !== '') {
                $haveUrls[$url] = true;
            }
        }
        foreach (self::resolveForGeneration($userId, $personaId, $groupIds) as $item) {
            $url = self::readySynthesisUrl((string)($item['url'] ?? ''));
            if ($url === '' || isset($haveUrls[$url])) {
                continue;
            }
            $row = [
                'id' => $item['id'],
                'persona_id' => $personaId,
                'user_id' => $userId,
                'material_name' => $item['name'],
                'material_type' => (int)$item['kind'],
                'file_url' => $url,
                'thumbnail_url' => (string)($item['cover_url'] ?? ''),
                'duration' => (int)($item['duration_sec'] ?? 0),
                'source' => 'square',
            ];
            if ((int)$item['kind'] === self::KIND_VIDEO) {
                $grouped['videos'][] = $row;
            } else {
                $grouped['images'][] = $row;
            }
            $haveUrls[$url] = true;
        }
        return $grouped;
    }

    public static function fillGroupedMaterials(array $grouped, int $userId, int $personaId, $config): array
    {
        $source = is_array($config)
            ? (int)($config['visual_material_source'] ?? 0)
            : (int)($config->visual_material_source ?? 0);
        if ($source !== 4) {
            return $grouped;
        }
        $groupIds = self::normalizeGroupIds(
            is_array($config)
                ? ($config['square_group_ids'] ?? [])
                : ($config->square_group_ids ?? [])
        );
        $haveUrls = [];
        foreach (array_merge($grouped['videos'] ?? [], $grouped['images'] ?? []) as $item) {
            $url = Material::rawFileUrl($item);
            if ($url !== '') {
                $haveUrls[$url] = true;
            }
        }
        $public = self::publicGrouped($userId, $personaId, $groupIds);
        foreach (['videos', 'images'] as $key) {
            foreach ($public[$key] as $row) {
                $url = (string)($row['file_url'] ?? '');
                if ($url === '' || isset($haveUrls[$url])) {
                    continue;
                }
                $grouped[$key][] = $row;
                $haveUrls[$url] = true;
            }
        }
        return $grouped;
    }

    public static function normalizeGroupIds($value): array
    {
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : explode(',', $value);
        }
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_unique(array_filter(array_map('intval', $value))));
    }

    public static function parseAssetId($id): int
    {
        $id = (string)$id;
        if (str_starts_with($id, self::LIST_ID_PREFIX)) {
            $id = substr($id, strlen(self::LIST_ID_PREFIX));
        }
        return (int)$id;
    }

    private static function cleanGroup(array $raw, array $old = []): array
    {
        $name = self::clip((string)self::pick($raw, $old, 'name', ''), self::GROUP_NAME_MAX);
        if ($name === '') {
            throw new \InvalidArgumentException('分组名称不能为空');
        }
        return [
            'name' => $name,
            'desc' => self::clip((string)self::pick($raw, $old, 'desc', ''), self::DESC_MAX),
            'enabled' => self::boolInt(self::pick($raw, $old, 'enabled', 1)),
            'sort' => self::intVal(self::pick($raw, $old, 'sort', 0)),
        ];
    }

    private static function cleanAsset(array $raw, array $old = []): array
    {
        $groupId = (int)self::pick($raw, $old, 'group_id', 0);
        $group = SquareAssetGroup::findOrEmpty($groupId);
        if ($group->isEmpty()) {
            throw new \InvalidArgumentException('素材必须归属一个已存在的分组');
        }

        $kind = (int)self::pick($raw, $old, 'kind', self::KIND_VIDEO);
        if (!in_array($kind, [self::KIND_VIDEO, self::KIND_IMAGE], true)) {
            throw new \InvalidArgumentException('素材类型只能是视频或图片');
        }

        $url = self::checkUrl((string)self::pick($raw, $old, 'url', ''), '素材地址');
        if ($url === '') {
            throw new \InvalidArgumentException('素材地址不能为空');
        }
        $cover = self::checkUrl((string)self::pick($raw, $old, 'cover_url', ''), '素材封面');
        $cover = self::ensureVideoCover($kind, $url, $cover);
        $name = self::clip((string)self::pick($raw, $old, 'name', ''), self::NAME_MAX);

        return [
            'group_id' => $groupId,
            'kind' => $kind,
            'name' => $name !== '' ? $name : '未命名素材',
            'url' => $url,
            'cover_url' => $cover,
            'duration_sec' => max(0, self::intVal(self::pick($raw, $old, 'duration_sec', 0))),
            'tags' => self::cleanTags(self::pick($raw, $old, 'tags', [])),
            'enabled' => self::boolInt(self::pick($raw, $old, 'enabled', 1)),
            'sort' => self::intVal(self::pick($raw, $old, 'sort', 0)),
        ];
    }

    private static function formatGroup(array $group, int $assetCount): array
    {
        return [
            'id' => (int)$group['id'],
            'name' => (string)$group['name'],
            'desc' => (string)($group['desc'] ?? ''),
            'enabled' => (int)$group['enabled'],
            'sort' => (int)$group['sort'],
            'asset_count' => $assetCount,
            'create_time' => (int)($group['create_time'] ?? 0),
            'update_time' => (int)($group['update_time'] ?? 0),
        ];
    }

    public static function formatAsset(array $asset, string $groupName): array
    {
        $tags = $asset['tags'] ?? [];
        if (!is_array($tags)) {
            $tags = [];
        }
        $url = (string)($asset['url'] ?? '');
        $kind = (int)$asset['kind'];
        $cover = self::ensureVideoCover(
            $kind,
            $url,
            (string)($asset['cover_url'] ?? ''),
            (int)($asset['id'] ?? 0)
        );
        return [
            'id' => (int)$asset['id'],
            'group_id' => (int)$asset['group_id'],
            'group_name' => $groupName,
            'kind' => $kind,
            'name' => (string)$asset['name'],
            'url' => $url,
            'cover_url' => $cover !== '' ? $cover : ($kind === self::KIND_IMAGE ? $url : ''),
            'duration_sec' => (int)($asset['duration_sec'] ?? 0),
            'tags' => array_values($tags),
            'enabled' => (int)$asset['enabled'],
            'sort' => (int)$asset['sort'],
            'create_time' => (int)($asset['create_time'] ?? 0),
            'update_time' => (int)($asset['update_time'] ?? 0),
        ];
    }

    private static function formatLiveRef(array $asset, string $groupName, int $refAt): array
    {
        $formatted = self::formatAsset($asset, $groupName);
        $formatted['alive'] = true;
        $formatted['ref_at'] = $refAt;
        $formatted['source'] = 'square';
        $formatted['list_id'] = self::LIST_ID_PREFIX . $formatted['id'];
        $formatted['material_name'] = $formatted['name'];
        $formatted['material_type'] = $formatted['kind'];
        $formatted['file_url'] = $formatted['url'];
        $formatted['thumbnail_url'] = $formatted['cover_url'];
        $formatted['duration'] = $formatted['duration_sec'];
        return $formatted;
    }

    private static function formatDeadRef(array $ref): array
    {
        $kind = (int)($ref['kind'] ?: self::KIND_VIDEO);
        $name = (string)($ref['name'] ?: '已下架的素材');
        return [
            'id' => (int)$ref['asset_id'],
            'list_id' => self::LIST_ID_PREFIX . (int)$ref['asset_id'],
            'group_id' => 0,
            'group_name' => (string)($ref['group_name'] ?? ''),
            'kind' => $kind,
            'name' => $name,
            'url' => '',
            'cover_url' => '',
            'duration_sec' => 0,
            'tags' => [],
            'enabled' => 0,
            'sort' => 0,
            'alive' => false,
            'ref_at' => (int)($ref['ref_at'] ?? 0),
            'source' => 'square',
            'material_name' => $name,
            'material_type' => $kind,
            'file_url' => '',
            'thumbnail_url' => '',
            'duration' => 0,
        ];
    }

    private static function pick(array $raw, array $old, string $key, $default)
    {
        if (array_key_exists($key, $raw)) {
            return $raw[$key];
        }
        if (array_key_exists($key, $old)) {
            return $old[$key];
        }
        return $default;
    }

    /**
     * 出片用：转码中的广场素材先跳过；已转完则用 File 当前 uri。
     */
    private static function readySynthesisUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        $relative = FileService::setFileUrl($url);
        $file = File::where('uri', $relative)->findOrEmpty();
        if ($file->isEmpty() && $relative !== $url) {
            $file = File::where('uri', $url)->findOrEmpty();
        }
        if ($file->isEmpty()) {
            return $url;
        }
        $status = (int)($file->transcode_status ?? 0);
        if ($status === 1 || $status === 2) {
            return '';
        }
        $live = trim((string)($file->uri ?? ''));
        return $live !== '' ? $live : $url;
    }

    /**
     * 视频没封面时截 0.5 秒帧，与人设素材入库同一套 ffmpeg。
     * $assetId 有值时把结果写回，给已经上架但没封面的旧数据补图。
     */
    private static function ensureVideoCover(int $kind, string $url, string $cover, int $assetId = 0): string
    {
        if ($cover !== '' || $kind !== self::KIND_VIDEO || $url === '') {
            return $cover;
        }
        try {
            $generated = self::checkUrl(MaterialService::makeVideoThumbnail($url), '素材封面');
        } catch (\Throwable $e) {
            return '';
        }
        if ($generated !== '' && $assetId > 0) {
            $row = SquareAsset::findOrEmpty($assetId);
            if (!$row->isEmpty()) {
                $row->cover_url = $generated;
                $row->save();
            }
        }
        return $generated;
    }

    private static function checkUrl(string $url, string $label): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (stripos($url, 'Expires=') !== false) {
            throw new \InvalidArgumentException($label . '是带签名的临时地址（含 Expires），请转存后再填');
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || !str_contains($url, '://')) {
            return FileService::setFileUrl($url);
        }
        throw new \InvalidArgumentException($label . '必须是 http(s) 公网直链');
    }

    private static function cleanTags($tags): array
    {
        if (is_string($tags)) {
            $tags = preg_split('/[,，]/u', $tags) ?: [];
        }
        if (!is_array($tags)) {
            return [];
        }
        $clean = [];
        foreach ($tags as $tag) {
            $tag = self::clip((string)$tag, self::TAG_MAX);
            if ($tag !== '') {
                $clean[] = $tag;
            }
        }
        return array_values(array_slice(array_unique($clean), 0, self::TAG_LIMIT));
    }

    private static function clip(string $value, int $max): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        return mb_strlen($value, 'UTF-8') > $max ? mb_substr($value, 0, $max, 'UTF-8') : $value;
    }

    private static function boolInt($value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        return (int)$value ? 1 : 0;
    }

    private static function intVal($value): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        return 0;
    }
}