<?php

declare(strict_types=1);

namespace app\common\service;

use app\api\logic\videoSynthesis\CopywritingImitationLogic;
use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\model\shanjian\SystemMusic;
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
            ->where('audio_url', '<>', '')
            ->order('sort', 'asc')
            ->order('update_time', 'desc')
            ->field('id,name,audio_url,duration_sec,sort')
            ->select()
            ->toArray();
        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'id' => (string)$row['id'],
                'name' => (string)$row['name'],
                'audio_url' => (string)$row['audio_url'],
                'duration_sec' => (int)$row['duration_sec'],
                'sort' => (int)$row['sort'],
            ];
        }
        return $list;
    }

    public static function isEnabled(string $id): bool
    {
        $id = trim($id);
        if ($id === '') {
            return false;
        }
        return !SystemMusic::where('id', $id)->where('enabled', 1)->where('audio_url', '<>', '')->findOrEmpty()->isEmpty();
    }

    public static function findEnabled(string $id): array
    {
        $id = trim($id);
        if ($id === '') {
            return [];
        }
        $row = SystemMusic::where('id', $id)->where('enabled', 1)->where('audio_url', '<>', '')->findOrEmpty();
        if ($row->isEmpty()) {
            return [];
        }
        return [
            'id' => (string)$row->id,
            'name' => (string)$row->name,
            'audio_url' => (string)$row->audio_url,
            'duration_sec' => (int)$row->duration_sec,
        ];
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
