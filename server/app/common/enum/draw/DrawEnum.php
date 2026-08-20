<?php

declare(strict_types=1);

namespace app\common\enum\draw;

/**
 * draw 生图/生视频枚举
 */
class DrawEnum
{
    /** media_type */
    public const MEDIA_IMAGE = 'image';
    public const MEDIA_VIDEO = 'video';
    /** PPT 会话（任务本体仍走 image 生图） */
    public const MEDIA_PPT = 'ppt';

    /** asset_type */
    public const ASSET_IMAGE = 'image';
    public const ASSET_VIDEO = 'video';
    public const ASSET_COVER = 'cover';

    /** task status */
    public const STATUS_PENDING = 0;
    public const STATUS_SUBMITTED = 1;
    public const STATUS_PROCESSING = 2;
    public const STATUS_SUCCESS = 3;
    public const STATUS_FAILED = 4;
    public const STATUS_CANCELLED = 5;

    /** bill_status */
    public const BILL_NONE = 0;
    public const BILL_HELD = 1;
    public const BILL_CONSUMED = 2;
    public const BILL_REFUNDED = 3;

    public static function isTerminal(int $status): bool
    {
        return in_array($status, [self::STATUS_SUCCESS, self::STATUS_FAILED, self::STATUS_CANCELLED], true);
    }
}
