<?php

namespace app\common\service\digitalHuman;

use app\common\service\OriginalMaterialSpec;

class ChanjingAnchorVideoValidator
{
    private const MIN_DURATION = 30;
    private const MAX_DURATION = 300;

    public static function validate(string $videoUrl): ?string
    {
        try {
            $spec = OriginalMaterialSpec::probeVideo($videoUrl);
        } catch (\Throwable $e) {
            $spec = null;
        }

        $duration = (float)($spec['duration'] ?? 0);
        if (!is_finite($duration) || $duration <= 0 || empty($spec['width']) || empty($spec['height'])) {
            return '无法读取形象视频时长，请重新上传视频';
        }
        if ($duration < self::MIN_DURATION || $duration > self::MAX_DURATION) {
            return sprintf('形象视频时长需为%d-%d秒，请重新上传', self::MIN_DURATION, self::MAX_DURATION);
        }

        return null;
    }
}
