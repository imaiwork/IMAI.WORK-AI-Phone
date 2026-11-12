<?php

namespace app\common\service\shanjian;

/**
 * 手动创作视频封面策略
 *
 * 与自动合成链路保持同一套 video_cover_source 语义：
 * 1=首帧（不向闪剪提交 firstFrameCover）、2=AI封面、3=手动上传。
 * AI 封面异步生成，建单时只把封面状态标为生成中，thumb_status 固定 4，避免阻塞视频合成。
 */
class ShanjianVideoCoverService
{
    /** 未设置：沿用旧逻辑，有图即作为 firstFrameCover 下发 */
    public const SOURCE_UNSET = 0;
    /** 首帧：由平台按视频首帧生成封面 */
    public const SOURCE_FIRST_FRAME = 1;
    /** AI封面：异步生成，仅回填 pic */
    public const SOURCE_AI = 2;
    /** 手动上传：作为 firstFrameCover 下发 */
    public const SOURCE_MANUAL = 3;

    /** 封面未生成 */
    public const COVER_STATUS_NONE = 0;
    /** 封面生成中 */
    public const COVER_STATUS_GENERATING = 1;
    /** 封面生成成功 */
    public const COVER_STATUS_SUCCESS = 2;
    /** 封面生成失败 */
    public const COVER_STATUS_FAILED = 3;

    /**
     * 解析建单时的封面字段
     *
     * @param int    $coverSource 封面方式
     * @param string $fallbackPic 兜底缩略图（形象封面/素材封面），仅列表展示
     * @param string $manualPic   用户手动上传的封面
     * @return array{pic:string,thumb_status:int,cover_status:int,cover_result_id:string,video_cover_source:int}
     */
    public static function buildForTask(int $coverSource, string $fallbackPic, string $manualPic = ''): array
    {
        $coverSource = self::normalizeSource($coverSource);
        $fallbackPic = trim($fallbackPic);
        $manualPic = trim($manualPic);

        switch ($coverSource) {
            case self::SOURCE_FIRST_FRAME:
                // 保留兜底图用于列表展示，thumb_status=4 表示不带封面下发
                return self::result($coverSource, $fallbackPic, 4, self::COVER_STATUS_NONE);
            case self::SOURCE_AI:
                // 视频照常合成，封面由定时任务异步提交并回填 pic
                return self::result($coverSource, $fallbackPic, 4, self::COVER_STATUS_GENERATING);
            case self::SOURCE_MANUAL:
                // 手动封面作为 firstFrameCover 下发；缺图时保留兜底图并不带封面
                return self::result(
                    $coverSource,
                    $manualPic !== '' ? $manualPic : $fallbackPic,
                    $manualPic !== '' ? 2 : 4,
                    self::COVER_STATUS_NONE
                );
            default:
                // 未设置：完全保持改造前行为
                return self::result(
                    self::SOURCE_UNSET,
                    $fallbackPic,
                    $fallbackPic !== '' ? 2 : 4,
                    self::COVER_STATUS_NONE
                );
        }
    }

    public static function normalizeSource(int $coverSource): int
    {
        return in_array($coverSource, [
            self::SOURCE_UNSET,
            self::SOURCE_FIRST_FRAME,
            self::SOURCE_AI,
            self::SOURCE_MANUAL,
        ], true) ? $coverSource : self::SOURCE_UNSET;
    }

    private static function result(int $source, string $pic, int $thumbStatus, int $coverStatus): array
    {
        return [
            'pic' => $pic,
            'thumb_status' => $thumbStatus,
            'cover_status' => $coverStatus,
            'cover_result_id' => '',
            'video_cover_source' => $source,
        ];
    }
}
