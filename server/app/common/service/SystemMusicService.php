<?php

declare(strict_types=1);

namespace app\common\service;

use app\api\logic\videoSynthesis\CopywritingImitationLogic;
use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\model\shanjian\SystemMusic;
use app\common\model\shanjian\SystemMusicGroup;
use app\common\model\sv\SvDevice;

/**
 * 系统曲库：上架曲目里随机，或按曲目 id 指定。
 * 本地表为空时回退静态 1-20.mp3，避免曲库还没同步时成片没有背景音。
 */
class SystemMusicService
{
    public static function publicList(): array
    {
        $rows = SystemMusic::where('enabled', 1)
            // 后台隐藏（status=0）的曲目不进前台列表，也不能被随机/指定选中
            ->where('status', 1)
            ->where('audio_url', '<>', '')
            // 本站排序优先，否则跟随模板中心的 sort（越小越靠前）
            ->orderRaw(SystemMusic::SORT_EXPRESSION . ' ASC, update_time DESC')
            ->field('id,name,audio_url,duration_sec,sort,group_id')
            ->select()
            ->toArray();
        // 不 join 分组表：SORT_EXPRESSION 里的 sort 不带表别名，join 之后会撞
        // 「字段名不明确」。分组名由 publicGroups() 单独下发，前端按 group_id 关联。
        $groupMap = self::groupNameMap();
        $list = [];
        foreach ($rows as $row) {
            $groupId = (int)($row['group_id'] ?? SystemMusicGroup::NO_GROUP);
            $list[] = [
                'id' => (string)$row['id'],
                'name' => (string)$row['name'],
                'audio_url' => (string)$row['audio_url'],
                'duration_sec' => (int)$row['duration_sec'],
                'sort' => (int)$row['sort'],
                // 0 = 无分组，用户端只在「全部」里出现
                'group_id' => $groupId,
                'group_name' => $groupMap[$groupId] ?? '',
            ];
        }
        return $list;
    }

    /**
     * 用户端选曲的「分组」tab 数据源。
     *
     * 只返回当前至少有一首可用曲目的分组：空分组在用户端就是个点进去空白的死 tab。
     * 无分组（group_id=0）的曲目不出 tab，只在「全部」里出现。
     *
     * @return array<int, array{id:int,name:string,music_count:int}>
     */
    /**
     * 配置页选分组用：列出全部分组（含暂时没有可用曲目的），并带上可用曲目数。
     *
     * 与 publicGroups() 的差别只在「空分组」：用户端空分组是死 tab 要隐藏，
     * 但站长配置背景音乐时得看得见自己建的分组（否则只显示「全部」，
     * 会误以为分组没生效）。空分组选中后保存仍会回落「全部」，前端按 music_count 提示。
     *
     * @return array<int, array{id:int,name:string,music_count:int}>
     */
    public static function allGroups(): array
    {
        $countMap = self::availableMusicCountByGroup();
        $list = [];
        $groups = SystemMusicGroup::field('id,name')
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        foreach ($groups as $group) {
            $id = (int)$group['id'];
            $list[] = [
                'id' => $id,
                'name' => (string)$group['name'],
                'music_count' => $countMap[$id] ?? 0,
            ];
        }
        return $list;
    }

    /** @return array<int, int> group_id => 可用曲目数 */
    private static function availableMusicCountByGroup(): array
    {
        $counts = SystemMusic::where('enabled', 1)
            ->where('status', 1)
            ->where('audio_url', '<>', '')
            ->where('group_id', '>', SystemMusicGroup::NO_GROUP)
            ->field('group_id, COUNT(*) AS music_count')
            ->group('group_id')
            ->select()
            ->toArray();
        $countMap = [];
        foreach ($counts as $row) {
            $countMap[(int)$row['group_id']] = (int)$row['music_count'];
        }
        return $countMap;
    }

    public static function publicGroups(): array
    {
        $countMap = self::availableMusicCountByGroup();
        if ($countMap === []) {
            return [];
        }

        $list = [];
        $groups = SystemMusicGroup::field('id,name')
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        foreach ($groups as $group) {
            $id = (int)$group['id'];
            if (($countMap[$id] ?? 0) <= 0) {
                continue;
            }
            $list[] = [
                'id' => $id,
                'name' => (string)$group['name'],
                'music_count' => $countMap[$id],
            ];
        }
        return $list;
    }

    /** @return array<int, string> group_id => 分组名 */
    private static function groupNameMap(): array
    {
        $rows = SystemMusicGroup::field('id,name')->select()->toArray();
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['id']] = (string)$row['name'];
        }
        return $map;
    }

    public static function isEnabled(string $id): bool
    {
        $id = trim($id);
        if ($id === '') {
            return false;
        }
        return !SystemMusic::where('id', $id)->where('enabled', 1)->where('status', 1)->where('audio_url', '<>', '')->findOrEmpty()->isEmpty();
    }

    public static function findEnabled(string $id): array
    {
        $id = trim($id);
        if ($id === '') {
            return [];
        }
        $row = SystemMusic::where('id', $id)->where('enabled', 1)->where('status', 1)->where('audio_url', '<>', '')->findOrEmpty();
        if ($row->isEmpty()) {
            return [];
        }
        return [
            'id' => (string)$row->id,
            'name' => (string)$row->name,
            'audio_url' => (string)$row->audio_url,
            'duration_sec' => (int)$row->duration_sec,
            'group_id' => (int)($row->group_id ?? SystemMusicGroup::NO_GROUP),
        ];
    }

    /**
     * 分组是否至少有一首可用曲目。
     * 配置保存时用它判断用户选的分组是否还有歌，空分组回落「全部」。
     */
    public static function groupHasMusic(int $groupId): bool
    {
        if ($groupId <= SystemMusicGroup::NO_GROUP) {
            return false;
        }
        return SystemMusic::where('enabled', 1)
            ->where('status', 1)
            ->where('audio_url', '<>', '')
            ->where('group_id', $groupId)
            ->count() > 0;
    }

    /**
     * 组内随机：只在该分组的可用曲目里取。
     * 分组为空（空分组 / 隐藏 / 同步下架）时回落全库随机，不能因为分组没歌就让成片静音。
     */
    public static function randomUrlInGroup(int $groupId): string
    {
        if ($groupId > SystemMusicGroup::NO_GROUP) {
            $rows = array_values(array_filter(self::publicList(), static function (array $row) use ($groupId) {
                return (int)($row['group_id'] ?? 0) === $groupId;
            }));
            if ($rows !== []) {
                $hit = $rows[array_rand($rows)];
                $url = trim((string)($hit['audio_url'] ?? ''));
                if ($url !== '' && !self::isLegacyPoolUrl($url)) {
                    return $url;
                }
            }
        }
        return self::randomUrl();
    }

    /**
     * 分组内解析背景音乐：
     * - 指定曲目上架且属于该分组 → 用它；
     * - 指定曲目属于别组或已下架 → 组内随机（$strict 为真时抛错，供保存校验用）；
     * - 未指定 → 组内随机。
     */
    public static function resolveInGroup(string $pinnedId, int $groupId, bool $strict = false): string
    {
        $pinnedId = trim($pinnedId);
        if ($pinnedId !== '') {
            $hit = self::findEnabled($pinnedId);
            if ($hit !== []) {
                $hitGroup = (int)($hit['group_id'] ?? SystemMusicGroup::NO_GROUP);
                // 分组只在「系统音乐库 + 指定分组」时才约束曲目归属；未分组配置不校验
                if ($groupId <= SystemMusicGroup::NO_GROUP || $hitGroup === $groupId) {
                    return (string)$hit['audio_url'];
                }
                if ($strict) {
                    throw new \Exception('这首系统音乐不在所选分组内，请重选或改回随机');
                }
            } elseif ($strict) {
                throw new \Exception('这首系统音乐已下架，请重选或改回随机');
            }
        }
        return self::randomUrlInGroup($groupId);
    }

    /**
     * @throws \Exception 指定了曲目但已下架，且 $strict 为真
     */
    public static function resolve(string $id, bool $strict): string
    {
        $id = trim($id);
        if ($id !== '') {
            $hit = self::findEnabled($id);
            if ($hit !== []) {
                return $hit['audio_url'];
            }
            if ($strict) {
                throw new \Exception('这首系统音乐已下架，请重选或改回随机');
            }
        }
        return self::randomUrl();
    }

    public static function randomUrl(): string
    {
        // 与选曲列表同一条查询。column() 在部分环境下拿不到 audio_url，
        // 会误落到不存在的静态 1-20.mp3，成片就没有背景音。
        $rows = self::publicList();
        if ($rows !== []) {
            $hit = $rows[array_rand($rows)];
            $url = trim((string)($hit['audio_url'] ?? ''));
            if ($url !== '' && !self::isLegacyPoolUrl($url)) {
                return $url;
            }
        }
        return self::legacyUrl();
    }

    public static function legacyUrl(): string
    {
        return rtrim((string)config('app.app_host'), '/') . '/static/audio/music/' . random_int(1, 20) . '.mp3';
    }

    /** 旧的 1-20.mp3 静态池。文件不在站点上时，闪剪拉不到，成片就没有背景音。 */
    public static function isLegacyPoolUrl(string $url): bool
    {
        return (bool)preg_match('#/static/audio/music/\d+\.mp3(?:\?.*)?$#i', trim($url));
    }

    /** 设备自动出片：有人设配置就跟人设（含指定曲目），否则在曲库里随机 */
    public static function resolveForDevice(string $deviceCode, int $personaId = 0): string
    {
        if ($personaId <= 0 && $deviceCode !== '') {
            $personaId = (int)SvDevice::where('device_code', $deviceCode)->value('persona_id');
        }
        if ($personaId > 0) {
            $config = AiPersonaSynthesisConfig::where('persona_id', $personaId)->findOrEmpty();
            if (!$config->isEmpty()) {
                return CopywritingImitationLogic::resolveMusicUrlByConfig($config, $personaId);
            }
        }
        return self::randomUrl();
    }
}
