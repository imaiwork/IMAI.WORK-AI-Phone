<?php

namespace app\common\service\copywriting;

use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaCopywritingLibrary;
use app\common\model\copywriting\PublicCopywriting;

class PublicCopywritingService
{
    public const TITLE_MAX = 30;
    public const CONTENT_MAX = 1200;
    public const TOPIC_MAX = 200;
    public const INDUSTRY_MAX = 16;
    public const MAX_OWNED = 200;
    public const AUTO_TITLE_LEN = 16;
    public const AUTO_TITLE_MIN_PLAIN = 6;

    public const NEED_CONFIRM = 'need_confirm';

    public const UI_TYPE_READY = 1;
    public const UI_TYPE_FRAME = 2;

    private const PLACEHOLDER = '/[{｛][^}｝]{0,12}[}｝]/u';

    public static function listAdmin(
        ?int $libraryType,
        ?int $driverType,
        ?int $uiType = null,
        int $pageNo = 1,
        int $pageSize = 15
    ): array {
        $pageNo = max(1, $pageNo);
        $pageSize = max(1, min(100, $pageSize));

        $query = PublicCopywriting::order(['sort' => 'asc', 'id' => 'desc']);
        if ($libraryType) {
            $query->where('library_type', $libraryType);
        }
        if ($driverType !== null && $driverType >= 0 && $libraryType) {
            $query->where('driver_type', $driverType);
        }
        if ($uiType) {
            $query->where('ui_type', $uiType);
        }

        $paginator = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $pageNo,
        ])->toArray();

        return [
            'count' => (int)($paginator['total'] ?? 0),
            'lists' => array_map([self::class, 'formatTemplate'], $paginator['data'] ?? []),
            'extend' => self::countByUiType(),
        ];
    }

    public static function countByUiType(): array
    {
        $rows = PublicCopywriting::field('ui_type, COUNT(*) AS cnt')
            ->group('ui_type')
            ->select()
            ->toArray();
        $ready = 0;
        $frame = 0;
        $all = 0;
        foreach ($rows as $row) {
            $cnt = (int)($row['cnt'] ?? 0);
            $all += $cnt;
            $uiType = (int)($row['ui_type'] ?? 0);
            if ($uiType === self::UI_TYPE_READY) {
                $ready = $cnt;
            } elseif ($uiType === self::UI_TYPE_FRAME) {
                $frame = $cnt;
            }
        }
        return [
            'all' => $all,
            'ready' => $ready,
            'frame' => $frame,
        ];
    }

    public static function create(array $raw): array
    {
        $data = self::cleanTemplate($raw);
        $row = PublicCopywriting::create($data);
        return self::formatTemplate($row->toArray());
    }

    public static function update(int $id, array $raw): array
    {
        $row = PublicCopywriting::findOrEmpty($id);
        if ($row->isEmpty()) {
            throw new \RuntimeException('文案不存在');
        }
        $data = self::cleanTemplate($raw, $row->toArray());
        $row->save($data);
        return self::formatTemplate($row->refresh()->toArray());
    }

    public static function delete(int $id): void
    {
        $row = PublicCopywriting::findOrEmpty($id);
        if ($row->isEmpty()) {
            throw new \RuntimeException('文案不存在');
        }
        $row->delete();
    }

    public static function browse(
        int $userId,
        int $personaId,
        ?int $libraryType,
        ?int $driverType,
        int $pageNo = 1,
        int $pageSize = 20
    ): array {
        self::assertPersona($userId, $personaId);

        $pageNo = max(1, $pageNo);
        $pageSize = max(1, min(100, $pageSize));

        $query = PublicCopywriting::where('enabled', 1)->order(['sort' => 'asc', 'id' => 'desc']);
        if ($libraryType) {
            $query->where('library_type', $libraryType);
        }
        if ($driverType !== null && $driverType >= 0 && $libraryType) {
            $query->where('driver_type', $driverType);
        }

        $paginator = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $pageNo,
        ])->toArray();
        $templates = $paginator['data'] ?? [];
        $tplIds = array_column($templates, 'id');

        $owned = $tplIds
            ? AiPersonaCopywritingLibrary::where('user_id', $userId)
                ->where('persona_id', $personaId)
                ->where('source', AiPersonaCopywritingLibrary::SOURCE_PUBLIC)
                ->whereIn('source_id', $tplIds)
                ->field('id,source_id,source_version,edited')
                ->select()
                ->toArray()
            : [];
        $mine = [];
        foreach ($owned as $row) {
            $mine[(int)$row['source_id']] = $row;
        }

        $items = [];
        foreach ($templates as $tpl) {
            $item = self::formatTemplate($tpl);
            $exist = $mine[(int)$tpl['id']] ?? null;
            $item['added'] = $exist ? 1 : 0;
            $item['edited'] = $exist ? (int)$exist['edited'] : 0;
            $item['has_update'] = $exist
                && !(int)$exist['edited']
                && (int)$exist['source_version'] < (int)$tpl['version']
                ? 1 : 0;
            $item['owned_id'] = $exist ? (int)$exist['id'] : 0;
            $items[] = $item;
        }

        return [
            'items' => $items,
            'lists' => $items,
            'count' => (int)($paginator['total'] ?? 0),
        ];
    }

    public static function addToPersona(int $userId, int $personaId, array $ids): array
    {
        self::assertPersona($userId, $personaId);
        $ids = self::normalizeIds($ids);
        if (!$ids) {
            throw new \RuntimeException('没有选中任何文案');
        }

        $persona = AiPersona::where('id', $personaId)->where('user_id', $userId)->findOrEmpty();
        $map = self::personaMap($persona->toArray());

        $templates = PublicCopywriting::whereIn('id', $ids)
            ->where('enabled', 1)
            ->select()
            ->toArray();
        $byId = [];
        foreach ($templates as $tpl) {
            $byId[(int)$tpl['id']] = $tpl;
        }

        $ownedRows = AiPersonaCopywritingLibrary::where('user_id', $userId)
            ->where('persona_id', $personaId)
            ->where('source', AiPersonaCopywritingLibrary::SOURCE_PUBLIC)
            ->whereIn('source_id', $ids)
            ->select();
        $ownedBySource = [];
        foreach ($ownedRows as $row) {
            $ownedBySource[(int)$row->source_id] = $row;
        }

        $publicCount = (int)AiPersonaCopywritingLibrary::where('user_id', $userId)
            ->where('persona_id', $personaId)
            ->where('source', AiPersonaCopywritingLibrary::SOURCE_PUBLIC)
            ->count();

        $added = 0;
        $updated = 0;
        $skipped = 0;
        $missing = 0;
        $capped = 0;
        $skippedTitles = [];
        $now = time();

        foreach ($ids as $id) {
            $tpl = $byId[$id] ?? null;
            if (!$tpl) {
                $missing++;
                continue;
            }
            $exist = $ownedBySource[$id] ?? null;
            if ($exist && (int)$exist->edited === 1) {
                $skipped++;
                $skippedTitles[] = (string)$exist->title;
                continue;
            }
            $payload = self::buildOwnedRow($userId, $personaId, $tpl, $map, $now);
            if ($exist) {
                unset($payload['create_time'], $payload['use_count'], $payload['last_used_time']);
                $exist->save($payload);
                $updated++;
                continue;
            }
            if ($publicCount >= self::MAX_OWNED) {
                $capped++;
                continue;
            }
            AiPersonaCopywritingLibrary::create($payload);
            $publicCount++;
            $added++;
        }

        $parts = [];
        if ($added) {
            $parts[] = '已加入 ' . $added . ' 条';
        }
        if ($updated) {
            $parts[] = '已更新 ' . $updated . ' 条';
        }
        if ($skipped) {
            $parts[] = $skipped . ' 条你手动改过，已保留未覆盖';
        }
        if ($missing) {
            $parts[] = $missing . ' 条已停用未加入';
        }
        if ($capped) {
            $parts[] = $capped . ' 条未加入（已达上限 ' . self::MAX_OWNED . ' 条）';
        }
        $message = $parts ? implode('；', $parts) : '已经是最新，没有需要同步的';

        return [
            'added' => $added,
            'updated' => $updated,
            'skipped' => $skipped,
            'missing' => $missing,
            'capped' => $capped,
            'skipped_titles' => $skippedTitles,
            'message' => $message,
        ];
    }

    public static function refresh(int $userId, int $libraryId, bool $force): array
    {
        $row = AiPersonaCopywritingLibrary::where('id', $libraryId)
            ->where('user_id', $userId)
            ->findOrEmpty();
        if ($row->isEmpty()) {
            throw new \RuntimeException('文案不存在');
        }
        if ((int)$row->source !== AiPersonaCopywritingLibrary::SOURCE_PUBLIC || (int)$row->source_id <= 0) {
            throw new \RuntimeException('这条文案不是从公共文案加入的，无法更新');
        }

        $tpl = PublicCopywriting::findOrEmpty((int)$row->source_id);
        if ($tpl->isEmpty() || (int)$tpl->enabled !== 1) {
            throw new \RuntimeException('原模板不可用，无法更新');
        }

        if ((int)$row->edited === 1 && !$force) {
            throw new \RuntimeException(self::NEED_CONFIRM);
        }

        $persona = AiPersona::where('id', (int)$row->persona_id)->where('user_id', $userId)->findOrEmpty();
        $map = $persona->isEmpty() ? [] : self::personaMap($persona->toArray());
        $payload = self::buildOwnedRow($userId, (int)$row->persona_id, $tpl->toArray(), $map, time());
        unset($payload['create_time'], $payload['use_count'], $payload['last_used_time']);
        $row->save($payload);
        return $row->refresh()->toArray();
    }

    public static function formatTemplate(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'title' => (string)$row['title'],
            'content' => (string)($row['content'] ?? ''),
            'topic' => (string)($row['topic'] ?? ''),
            'ui_type' => (int)($row['ui_type'] ?? self::UI_TYPE_READY),
            'library_type' => (int)$row['library_type'],
            'driver_type' => (int)$row['driver_type'],
            'industry' => (string)($row['industry'] ?? ''),
            'sort' => (int)($row['sort'] ?? 0),
            'enabled' => (int)($row['enabled'] ?? 1),
            'version' => (int)($row['version'] ?? 1),
            'create_time' => $row['create_time'] ?? 0,
            'update_time' => $row['update_time'] ?? 0,
        ];
    }

    public static function attachListFlags(array $list): array
    {
        $sourceIds = [];
        foreach ($list as $item) {
            $sid = (int)($item['source_id'] ?? 0);
            if ($sid > 0) {
                $sourceIds[] = $sid;
            }
        }
        $versions = [];
        $alive = [];
        if ($sourceIds) {
            $tpls = PublicCopywriting::whereIn('id', array_unique($sourceIds))
                ->field('id,version,enabled,delete_time')
                ->select()
                ->toArray();
            foreach ($tpls as $tpl) {
                $versions[(int)$tpl['id']] = (int)$tpl['version'];
                $alive[(int)$tpl['id']] = (int)$tpl['enabled'] === 1;
            }
        }

        foreach ($list as &$item) {
            $sourceId = (int)($item['source_id'] ?? 0);
            $item['source'] = (int)($item['source'] ?? AiPersonaCopywritingLibrary::SOURCE_MANUAL);
            $item['source_id'] = $sourceId;
            $item['source_version'] = (int)($item['source_version'] ?? 0);
            $item['edited'] = (int)($item['edited'] ?? 0);
            $item['from_public'] = $sourceId > 0 ? 1 : 0;
            $item['source_alive'] = $sourceId > 0 && !empty($alive[$sourceId]) ? 1 : 0;
            $item['has_update'] = $sourceId > 0
                && !(int)$item['edited']
                && !empty($alive[$sourceId])
                && isset($versions[$sourceId])
                && (int)$item['source_version'] < $versions[$sourceId]
                ? 1 : 0;
        }
        unset($item);
        return $list;
    }

    private static function cleanTemplate(array $raw, ?array $old = null): array
    {
        $uiType = self::pickInt($raw, $old, 'ui_type', self::UI_TYPE_READY);
        if (!in_array($uiType, [self::UI_TYPE_READY, self::UI_TYPE_FRAME], true)) {
            throw new \RuntimeException('请选择文案用途');
        }

        $libraryType = self::pickInt($raw, $old, 'library_type', AiPersonaCopywritingLibrary::LIBRARY_TYPE_VIDEO_DRIVER);
        $driverType = self::pickInt($raw, $old, 'driver_type', AiPersonaCopywritingLibrary::DRIVER_TYPE_ORAL);
        self::assertType($libraryType, $driverType);

        $title = mb_substr(trim((string)self::pick($raw, $old, 'title', '')), 0, self::TITLE_MAX);
        $content = mb_substr(trim((string)self::pick($raw, $old, 'content', '')), 0, self::CONTENT_MAX);
        $topic = mb_substr(trim((string)self::pick($raw, $old, 'topic', '')), 0, self::TOPIC_MAX);
        if ($content === '') {
            throw new \RuntimeException('正文还没写，客户那边没东西可挑');
        }
        if ($libraryType === AiPersonaCopywritingLibrary::LIBRARY_TYPE_PUBLISH) {
            $driverType = AiPersonaCopywritingLibrary::DRIVER_TYPE_NONE;
        } else {
            $topic = '';
        }
        if ($title === '') {
            $title = self::autoTitle($content);
        }

        $industry = mb_substr(trim((string)self::pick($raw, $old, 'industry', '')), 0, self::INDUSTRY_MAX);
        $sort = (int)self::pick($raw, $old, 'sort', 0);
        $enabled = (int)self::pick($raw, $old, 'enabled', 1) ? 1 : 0;

        $version = (int)($old['version'] ?? 0);
        $bump = !$old
            || (string)($old['title'] ?? '') !== $title
            || (string)($old['content'] ?? '') !== $content
            || (string)($old['topic'] ?? '') !== $topic
            || (int)($old['ui_type'] ?? 0) !== $uiType
            || (int)($old['library_type'] ?? 0) !== $libraryType
            || (int)($old['driver_type'] ?? 0) !== $driverType;
        if ($bump) {
            $version++;
        }
        if ($version < 1) {
            $version = 1;
        }

        return [
            'title' => $title,
            'content' => $content,
            'topic' => $topic,
            'ui_type' => $uiType,
            'library_type' => $libraryType,
            'driver_type' => $driverType,
            'industry' => $industry,
            'sort' => $sort,
            'enabled' => $enabled,
            'version' => $version,
        ];
    }

    private static function buildOwnedRow(int $userId, int $personaId, array $tpl, array $map, int $now): array
    {
        $title = self::rewrite((string)$tpl['title'], $map);
        $content = self::rewrite((string)($tpl['content'] ?? ''), $map);
        $topic = self::rewrite((string)($tpl['topic'] ?? ''), $map);
        if ((int)$tpl['library_type'] === AiPersonaCopywritingLibrary::LIBRARY_TYPE_VIDEO_DRIVER
            && (int)$tpl['driver_type'] === AiPersonaCopywritingLibrary::DRIVER_TYPE_NEWS) {
            $title = $content !== '' ? $content : $title;
            $content = '';
        }

        return [
            'user_id' => $userId,
            'persona_id' => $personaId,
            'library_type' => (int)$tpl['library_type'],
            'driver_type' => (int)$tpl['driver_type'],
            'title' => $title,
            'content' => $content,
            'topic' => $topic,
            'source' => AiPersonaCopywritingLibrary::SOURCE_PUBLIC,
            'source_id' => (int)$tpl['id'],
            'source_version' => (int)($tpl['version'] ?? 1),
            'edited' => 0,
            'sort' => 0,
            'status' => AiPersonaCopywritingLibrary::STATUS_ENABLED,
            'use_count' => 0,
            'last_used_time' => 0,
            'create_time' => $now,
            'update_time' => $now,
        ];
    }

    public static function rewrite(string $text, array $persona): string
    {
        if ($text === '' || !$persona) {
            return $text === '' ? '' : preg_replace(self::PLACEHOLDER, '', $text);
        }
        $mapping = [
            '姓名' => $persona['name'] ?? '',
            '名字' => $persona['name'] ?? '',
            '行业' => $persona['industry'] ?? '',
            '所在地' => $persona['city'] ?? '',
            '城市' => $persona['city'] ?? '',
            '地区' => $persona['city'] ?? '',
            '服务区域' => $persona['city'] ?? '',
            '主要服务' => $persona['service'] ?? '',
            '目标客户' => $persona['customers'] ?? '',
            '卖点' => $persona['selling'] ?? '',
            '为什么选你' => $persona['selling'] ?? '',
        ];
        $out = $text;
        foreach ($mapping as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $out = str_replace('{' . $key . '}', (string)$value, $out);
            $out = str_replace('｛' . $key . '｝', (string)$value, $out);
        }
        return trim((string)preg_replace(self::PLACEHOLDER, '', $out));
    }

    public static function personaMap(array $persona): array
    {
        return [
            'name' => trim((string)($persona['persona_name'] ?? '')),
            'industry' => trim((string)($persona['industry'] ?? '')),
            'city' => trim((string)($persona['store_position'] ?? '')),
            'service' => trim((string)($persona['main_business'] ?? '')),
            'customers' => trim((string)($persona['target_pain_points'] ?? '')),
            'selling' => trim((string)($persona['conversion_hook'] ?? '')),
        ];
    }

    public static function autoTitle(string $text): string
    {
        $text = trim($text);
        $plain = trim((string)preg_replace(self::PLACEHOLDER, '', $text));
        $source = mb_strlen($plain) < self::AUTO_TITLE_MIN_PLAIN ? $text : $plain;
        return mb_substr($source, 0, self::AUTO_TITLE_LEN);
    }

    private static function assertType(int $libraryType, int $driverType): void
    {
        if ($libraryType === AiPersonaCopywritingLibrary::LIBRARY_TYPE_PUBLISH) {
            return;
        }
        if ($libraryType === AiPersonaCopywritingLibrary::LIBRARY_TYPE_VIDEO_DRIVER
            && in_array($driverType, [
                AiPersonaCopywritingLibrary::DRIVER_TYPE_NEWS,
                AiPersonaCopywritingLibrary::DRIVER_TYPE_ORAL,
                AiPersonaCopywritingLibrary::DRIVER_TYPE_MATERIAL_MIXCUT,
            ], true)
        ) {
            return;
        }
        throw new \RuntimeException('请选择入库分类');
    }

    private static function assertPersona(int $userId, int $personaId): void
    {
        if ($personaId <= 0) {
            throw new \RuntimeException('人设不能为空');
        }
        $persona = AiPersona::where('id', $personaId)->where('user_id', $userId)->findOrEmpty();
        if ($persona->isEmpty()) {
            throw new \RuntimeException('人设不存在');
        }
    }

    private static function normalizeIds(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $out[$id] = $id;
            }
        }
        return array_values($out);
    }

    private static function pick(array $raw, ?array $old, string $key, $default)
    {
        if (array_key_exists($key, $raw)) {
            return $raw[$key];
        }
        if ($old && array_key_exists($key, $old)) {
            return $old[$key];
        }
        return $default;
    }

    private static function pickInt(array $raw, ?array $old, string $key, int $default): int
    {
        return (int)self::pick($raw, $old, $key, $default);
    }
}
