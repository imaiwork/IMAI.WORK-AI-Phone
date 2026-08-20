<?php

declare(strict_types=1);

namespace app\common\service\aiPersona;

use app\common\model\sv\SvDeviceViralRecord;

/**
 * 爆款分享文精确 hash + 清洗标题相似度去重
 */
class ViralTitleDedupService
{
    public const SIMILARITY_THRESHOLD = 0.70;

    public const MIN_TITLE_LENGTH = 4;

    public const LOOKBACK_SECONDS = 30 * 86400;

    /**
     * @return array{duplicate: bool, hash: string, title_normalized: string, reason: string, matched_id: int}
     */
    public static function isDuplicate(int $userId, string $shareContent): array
    {
        $titleNormalized = ViralShareTextNormalizer::normalize($shareContent);
        $url = ViralShareTextNormalizer::extractUrl($shareContent);
        $hashSource = $url !== '' ? $url : ($titleNormalized !== '' ? $titleNormalized : $shareContent);
        $hash = hash('sha256', $hashSource);

        $hashHit = SvDeviceViralRecord::where('user_id', $userId)
            ->where('hash', $hash)
            ->where('create_time', '>', time() - self::LOOKBACK_SECONDS)
            ->order('id', 'desc')
            ->findOrEmpty();
        if (!$hashHit->isEmpty()) {
            return [
                'duplicate' => true,
                'hash' => $hash,
                'title_normalized' => $titleNormalized,
                'reason' => 'hash',
                'matched_id' => (int)$hashHit->id,
            ];
        }

        if (mb_strlen($titleNormalized, 'UTF-8') < self::MIN_TITLE_LENGTH) {
            return [
                'duplicate' => false,
                'hash' => $hash,
                'title_normalized' => $titleNormalized,
                'reason' => '',
                'matched_id' => 0,
            ];
        }

        $titles = SvDeviceViralRecord::where('user_id', $userId)
            ->where('create_time', '>', time() - self::LOOKBACK_SECONDS)
            ->where('title_normalized', '<>', '')
            ->order('id', 'desc')
            ->limit(500)
            ->column('title_normalized', 'id');

        foreach ($titles as $id => $existingTitle) {
            $existingTitle = (string)$existingTitle;
            if ($existingTitle === '') {
                continue;
            }
            if (self::similarity($titleNormalized, $existingTitle) >= self::SIMILARITY_THRESHOLD) {
                return [
                    'duplicate' => true,
                    'hash' => $hash,
                    'title_normalized' => $titleNormalized,
                    'reason' => 'similarity',
                    'matched_id' => (int)$id,
                ];
            }
        }

        return [
            'duplicate' => false,
            'hash' => $hash,
            'title_normalized' => $titleNormalized,
            'reason' => '',
            'matched_id' => 0,
        ];
    }

    public static function similarity(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }
        if ($a === $b) {
            return 1.0;
        }
        similar_text($a, $b, $percent);
        return (float)$percent / 100;
    }
}
