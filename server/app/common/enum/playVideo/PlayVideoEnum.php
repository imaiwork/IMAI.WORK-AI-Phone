<?php

declare(strict_types=1);

namespace app\common\enum\playVideo;

/**
 * 爆款玩法枚举。bill_status 数值对齐 DrawEnum(0未扣/1预扣/2已结算/3已退款)。
 */
class PlayVideoEnum
{
    // 玩法
    public const PLAY_FACE = 'face';
    public const PLAY_DANCE = 'dance';
    public const PLAY_CLIP = 'clip'; // 影视引流拼接(IMS)
    public const PLAYS = [self::PLAY_FACE, self::PLAY_DANCE, self::PLAY_CLIP];

    // 任务状态
    public const STATUS_PENDING = 0;    // 待提交(建单未过中台)
    public const STATUS_GENERATING = 1; // 生成中
    public const STATUS_SUCCESS = 2;
    public const STATUS_FAILED = 3;
    public const STATUS_TIMEOUT = 4;
    public const TERMINAL_STATUSES = [self::STATUS_SUCCESS, self::STATUS_FAILED, self::STATUS_TIMEOUT];

    // 计费状态(对齐 DrawEnum)
    public const BILL_NONE = 0;
    public const BILL_HELD = 1;
    public const BILL_CONSUMED = 2;
    public const BILL_REFUNDED = 3;

    // 玩法 → model_config 场景名(一口价在后台可调)
    public const SCENES = [
        self::PLAY_FACE => 'play_face',
        self::PLAY_DANCE => 'play_dance',
        self::PLAY_CLIP => 'play_splice',
    ];

    // 用户同时在途任务上限
    public const MAX_USER_RUNNING = 3;

    /** 影视引流拼接用户视频时长上限(秒):成片=切片+整段用户视频,不封顶预扣与实扣能差十倍(与中台/前端一致) */
    public const CLIP_PRODUCT_MAX_SEC = 90;

    // 台词按预估口播时长限制(与中台一致):语速 4.5 字/秒,上限 60 秒 ≈ 270 字
    public const SPEECH_RUNES_PER_SECOND = 4.5;
    public const MAX_SPEECH_SECONDS = 60;
    // 口播字数下限(与前端一致):对口型只收不短于 1 秒的配音,按 4.5 字/秒取 6 字留余量
    public const MIN_SPEECH_RUNES = 6;

    // 站长侧兜底超时(秒):比中台 30min 晚 5 分钟,以中台终态为准
    public const TASK_TIMEOUT_SECONDS = 2100;

    public static function statusDesc(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => '待提交',
            self::STATUS_GENERATING => '生成中',
            self::STATUS_SUCCESS => '生成成功',
            self::STATUS_FAILED => '生成失败',
            self::STATUS_TIMEOUT => '生成超时',
            default => '未知',
        };
    }

    public static function billingCode(string $play): int
    {
        return match ($play) {
            self::PLAY_FACE => \app\common\enum\user\AccountLogEnum::TOKENS_DEC_PLAY_FACE,
            self::PLAY_DANCE => \app\common\enum\user\AccountLogEnum::TOKENS_DEC_PLAY_DANCE,
            self::PLAY_CLIP => \app\common\enum\user\AccountLogEnum::TOKENS_DEC_PLAY_SPLICE,
            default => 0,
        };
    }
}
