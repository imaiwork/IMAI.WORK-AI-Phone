<?php

namespace app\common\service;

use app\common\model\human\HumanVoice;
use app\common\model\shanjian\ShanjianAnchor;
use think\facade\Cache;
use think\facade\Log;

/**
 * 闪剪多 key 池：把本站形象/音色资产上报中台建立「资产级 key 绑定」。
 *
 * - 实时上报：形象/音色成功落库点调用 reportAvatarRow()/reportVoiceRow()，
 *   失败只记日志不影响业务（中台幂等，cron 会兜底补报）。
 * - 存量批量：syncBatch() 用 Redis 真游标（anchor/voice 各一条，记已上报 max id），
 *   修复旧版游标常量从未使用、每轮重复捞前 50 条的 bug。游标丢失重推无害。
 * - 软删行天然排除：两个模型均为软删模型，模型查询自带 delete_time IS NULL。
 *
 * 上报口径（终态才报）：
 * - 形象：anchor_id 非空；极速克隆(clone_type=1) status IN (6,9)，专业克隆(clone_type=2) status IN (3,9)。
 *   9 为会员冻结态，冻结前已训练成功、资产在上游真实存在，同样绑定。
 * - 音色：model_version IN (8,15)、voice_id 非空、status IN (1,9)；带重建的公网 audioUrl 供中台跨 key 重克隆。
 * - 极速克隆形象自带的音色（voice_id）额外补报一条音色资产，源音频用形象的 anchor_url——
 *   中台跨 key 冲突按 speakerId 查音色行，缺行则无法重克隆。
 */
class ShanjianAssetReportService
{
    private const BATCH_SIZE = 50;
    private const MAX_BATCHES_PER_RUN = 20;
    private const CURSOR_ANCHOR_KEY = 'shanjian_asset_report:cursor:anchor';
    private const CURSOR_VOICE_KEY = 'shanjian_asset_report:cursor:voice';
    private const SYNCED_FLAG_KEY = 'shanjian_asset_report:synced';

    /**
     * 存量资产是否已全部推送到中台（cron 追平后置位，永久有效）。
     * 训练请求据此决定是否带 X-Shanjian-Assets-Synced 能力标头——
     * 未追平前中台恒用默认 key，避免"新 key 形象 + 未上报存量音色"的跨 key 死局。
     * Redis 清空时标记与游标一起丢：全量重推、追平后自动恢复，行为自洽。
     */
    public static function isSynced(): bool
    {
        return (bool)Cache::get(self::SYNCED_FLAG_KEY, false);
    }

    /**
     * 实时上报一条形象记录（含极速克隆串联音色的补报）。失败只记日志。
     */
    public static function reportAvatarRow($anchor): void
    {
        try {
            $row = is_array($anchor) ? $anchor : $anchor->toArray();
            $items = self::buildAvatarItems($row);
            if (empty($items)) {
                return;
            }
            self::send($items, 'realtime-avatar');
        } catch (\Throwable $e) {
            Log::channel('shanjiannotice')->write('闪剪资产实时上报(形象)失败: ' . $e->getMessage());
        }
    }

    /**
     * 实时上报一条独立音色记录。失败只记日志。
     */
    public static function reportVoiceRow($voice): void
    {
        try {
            $row = is_array($voice) ? $voice : $voice->toArray();
            $item = self::buildVoiceItem($row);
            if ($item === null) {
                return;
            }
            self::send([$item], 'realtime-voice');
        } catch (\Throwable $e) {
            Log::channel('shanjiannotice')->write('闪剪资产实时上报(音色)失败: ' . $e->getMessage());
        }
    }

    /**
     * 存量批量上报（cron 入口）：anchor/voice 双游标推进，直到本轮配额用完或无新数据。
     */
    public static function syncBatch(): array
    {
        $totalSent = 0;
        $totalAccepted = 0;
        $totalSkipped = 0;

        for ($i = 0; $i < self::MAX_BATCHES_PER_RUN; $i++) {
            [$sent, $accepted, $skipped] = self::pushOneBatch();
            $totalSent += $sent;
            $totalAccepted += $accepted;
            $totalSkipped += $skipped;
            if ($sent === 0) {
                if ($skipped === 0) {
                    // 两张表都捞不到新行 = 存量已全部追平，置位能力标记。
                    // sent=0 但 skipped>0 说明本批全被跳过（如源音频重建失败），
                    // 游标已推进，留待下轮 cron 继续，先不置位。
                    Cache::set(self::SYNCED_FLAG_KEY, 1);
                }
                break;
            }
        }

        return ['sent' => $totalSent, 'accepted' => $totalAccepted, 'skipped' => $totalSkipped];
    }

    /**
     * @return array{0:int,1:int,2:int} [sent, accepted, skipped]
     */
    private static function pushOneBatch(): array
    {
        $items = [];
        $skipped = 0;

        $anchorCursor = (int)Cache::get(self::CURSOR_ANCHOR_KEY, 0);
        $anchors = ShanjianAnchor::where('id', '>', $anchorCursor)
            ->where('anchor_id', '<>', '')
            ->whereNotNull('anchor_id')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('clone_type', 1)->whereIn('status', [6, 9]);
                })->whereOr(function ($q2) {
                    $q2->where('clone_type', 2)->whereIn('status', [3, 9]);
                });
            })
            ->order('id', 'asc')
            ->limit(self::BATCH_SIZE)
            ->select()
            ->toArray();

        $maxAnchorId = $anchorCursor;
        foreach ($anchors as $row) {
            $maxAnchorId = max($maxAnchorId, (int)$row['id']);
            $built = self::buildAvatarItems($row);
            if (empty($built)) {
                $skipped++;
                continue;
            }
            $items = array_merge($items, $built);
        }

        $voiceCursor = (int)Cache::get(self::CURSOR_VOICE_KEY, 0);
        $maxVoiceId = $voiceCursor;
        if (count($anchors) < self::BATCH_SIZE) {
            $voices = HumanVoice::where('id', '>', $voiceCursor)
                ->whereIn('model_version', HumanVoice::SHANJIAN_VOICE_MODEL_VERSIONS)
                ->whereIn('status', [1, 9])
                ->where('voice_id', '<>', '')
                ->whereNotNull('voice_id')
                ->order('id', 'asc')
                ->limit(self::BATCH_SIZE - count($anchors))
                ->select()
                ->toArray();

            foreach ($voices as $row) {
                $maxVoiceId = max($maxVoiceId, (int)$row['id']);
                $item = self::buildVoiceItem($row);
                if ($item === null) {
                    $skipped++;
                    continue;
                }
                $items[] = $item;
            }
        }

        if (empty($items)) {
            // 空批也要推进游标：本批可能全是重建失败被跳过的行，不推进会死循环
            self::saveCursors($maxAnchorId, $maxVoiceId);
            return [0, 0, $skipped];
        }

        $response = self::send($items, 'cron');
        $accepted = (int)($response['data']['accepted'] ?? 0);
        $remoteSkipped = (int)($response['data']['skipped'] ?? 0);

        // 发送成功才推进游标，失败下轮重推（中台幂等）
        self::saveCursors($maxAnchorId, $maxVoiceId);

        return [count($items), $accepted, $skipped + $remoteSkipped];
    }

    private static function saveCursors(int $anchorId, int $voiceId): void
    {
        if ($anchorId > (int)Cache::get(self::CURSOR_ANCHOR_KEY, 0)) {
            Cache::set(self::CURSOR_ANCHOR_KEY, $anchorId);
        }
        if ($voiceId > (int)Cache::get(self::CURSOR_VOICE_KEY, 0)) {
            Cache::set(self::CURSOR_VOICE_KEY, $voiceId);
        }
    }

    /**
     * 形象行 → 上报 items：形象绑定一条；极速克隆已串出的音色再补一条音色资产。
     */
    private static function buildAvatarItems(array $row): array
    {
        $anchorId = trim((string)($row['anchor_id'] ?? ''));
        if ($anchorId === '') {
            return [];
        }
        $voiceId = trim((string)($row['voice_id'] ?? ''));
        $items = [[
            'station_user_id' => (string)($row['user_id'] ?? ''),
            'asset_type' => 'avatar',
            'avatar_id' => $anchorId,
            'speaker_id' => $voiceId,
        ]];
        if ($voiceId !== '') {
            // 音色源音频 = 形象训练视频（updateAnchor 串联 voice_train 用的就是 anchor_url）
            $audioUrl = self::rebuildPublicUrl($row['anchor_url'] ?? '');
            if ($audioUrl === '') {
                Log::channel('shanjiannotice')->write(
                    '闪剪资产上报：形象音色源音频重建失败 anchor_id=' . $anchorId . ' voice_id=' . $voiceId
                );
            }
            $items[] = [
                'station_user_id' => (string)($row['user_id'] ?? ''),
                'asset_type' => 'voice',
                'voice_id' => $voiceId,
                'audio_url' => $audioUrl,
            ];
        }
        return $items;
    }

    /**
     * 独立音色行 → 上报 item。源音频优先训练原音频，回落克隆出的示例音。
     * 重建失败仍上报绑定（缺源音频只是无法跨 key 重克隆），记日志不静默丢。
     */
    private static function buildVoiceItem(array $row): ?array
    {
        $voiceId = trim((string)($row['voice_id'] ?? ''));
        if ($voiceId === '') {
            return null;
        }
        $audioUrl = self::rebuildPublicUrl($row['audio_url'] ?? '');
        if ($audioUrl === '') {
            // 兼容历史字段 voice_urls（可能是 JSON 数组或单 URL）
            $voiceUrls = $row['voice_urls'] ?? '';
            if (is_string($voiceUrls) && $voiceUrls !== '' && $voiceUrls[0] === '[') {
                $decoded = json_decode($voiceUrls, true);
                $voiceUrls = is_array($decoded) ? (string)($decoded[0] ?? '') : '';
            }
            $audioUrl = self::rebuildPublicUrl(is_string($voiceUrls) ? $voiceUrls : '');
        }
        if ($audioUrl === '') {
            Log::channel('shanjiannotice')->write('闪剪资产上报：音色源音频重建失败 voice_id=' . $voiceId);
        }
        return [
            'station_user_id' => (string)($row['user_id'] ?? ''),
            'asset_type' => 'voice',
            'voice_id' => $voiceId,
            'audio_url' => $audioUrl,
            'model' => HumanVoice::shanjianTrainModel($row['model_version'] ?? HumanVoice::MODEL_VERSION_SHANJIAN),
        ];
    }

    private static function send(array $items, string $source): array
    {
        $response = ToolsService::Shanjian()->reportAssets($items);
        Log::channel('shanjiannotice')->write('闪剪资产上报(' . $source . '): ' . json_encode([
            'sent' => count($items),
            'accepted' => $response['data']['accepted'] ?? null,
            'skipped' => $response['data']['skipped'] ?? null,
        ], JSON_UNESCAPED_UNICODE));
        return $response;
    }

    /**
     * 站内相对路径/旧域名 URL 重建为当前存储配置下的公网 URL。
     * 去掉域名后按当前存储配置补全；路径缺失则返回空串（调用方决定是否跳过）。
     */
    public static function rebuildPublicUrl(?string $raw): string
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return '';
        }
        $path = FileService::setFileUrl($raw);
        $path = ltrim((string)$path, '/');
        if ($path === '' || str_contains($path, 'http://') || str_contains($path, 'https://')) {
            // setFileUrl 未能去域名或结果仍异常
            if (preg_match('#^https?://[^/]+(/.*)$#i', $raw, $m)) {
                $path = ltrim($m[1], '/');
            } else {
                return '';
            }
        }
        if ($path === '') {
            return '';
        }
        return FileService::getFileUrl($path, '', true);
    }
}
