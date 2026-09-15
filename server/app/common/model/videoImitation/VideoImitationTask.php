<?php

namespace app\common\model\videoImitation;

use app\common\model\BaseModel;
use app\common\service\FileService;
use app\common\service\ShanjianQueueService;
use think\model\concern\SoftDelete;

/**
 * 视频仿写任务模型（含小红书图文）
 */
class VideoImitationTask extends BaseModel
{
    use SoftDelete;

    protected $name = 'video_imitation_task';

    protected $deleteTime = 'delete_time';

    public const MEDIA_TYPE_VIDEO = 1;
    public const MEDIA_TYPE_IMAGE_TEXT = 2;

    /** 文案模式：1人设复刻（兼容默认），2不使用人设的洗稿 */
    public const REWRITE_MODE_PERSONA = 1;
    public const REWRITE_MODE_WASH = 2;

    /** 洗稿音乐音量/语速未设置哨兵（0 是合法音量，不能当哨兵） */
    public const WASH_PARAM_UNSET = -1.0;

    /**
     * 仿写文案目标字数档位。成片时长按「字数/3」估算，所以字数≈秒数×3：
     * 100≈30秒 200≈1分钟 350≈2分钟 600≈3分钟。
     * 不设「不限」档：中台 /api/coze/settext 对 length=0 会强制补 150，
     * 表达不了「不限」，最长档 600 已覆盖接入前的实际长度。
     */
    public const COPYWRITING_LENGTH_OPTIONS = [100, 200, 350, 600];
    public const COPYWRITING_LENGTH_DEFAULT = 200;

    /** 口播语速估算：闪剪成片时长按每秒 3 字换算 */
    public const SPEECH_CHARS_PER_SECOND = 3;

    /**
     * 归一目标字数：非法值一律回落默认档，绝不把脏值发给中台
     */
    public static function normalizeCopywritingLength($value): int
    {
        $length = is_numeric($value) ? (int)$value : 0;

        return in_array($length, self::COPYWRITING_LENGTH_OPTIONS, true)
            ? $length
            : self::COPYWRITING_LENGTH_DEFAULT;
    }

    /** 洗稿视频生成类型 */
    public const GENERATION_TYPE_NONE = 0;
    public const GENERATION_TYPE_DIGITAL_HUMAN = 1;
    public const GENERATION_TYPE_MATERIAL = 2;
    public const GENERATION_TYPE_NEWS = 3;

    public const IMAGE_REWRITE_STATUS_NONE = 0;
    public const IMAGE_REWRITE_STATUS_WAIT = 1;
    public const IMAGE_REWRITE_STATUS_PROCESSING = 2;
    public const IMAGE_REWRITE_STATUS_SUCCESS = 3;
    public const IMAGE_REWRITE_STATUS_FAIL = 4;
    /** 待用户确认要改写的图片 */
    public const IMAGE_REWRITE_STATUS_SELECTING = 5;

    /** 0解析中 1待确认文案 2生成中 3成功 4失败 */
    public const STATUS_PARSING = 0;
    public const STATUS_WAIT_CONFIRM = 1;
    public const STATUS_GENERATING = 2;
    public const STATUS_SUCCESS = 3;
    public const STATUS_FAIL = 4;

    public function searchStartTimeAttr($query, $value)
    {
        if ($value) {
            $query->where('t.create_time', '>=', strtotime($value));
        }
    }

    public function searchEndTimeAttr($query, $value)
    {
        if ($value) {
            $query->where('t.create_time', '<=', strtotime($value));
        }
    }

    public function getQueueStatusTextAttr($value, array $data): string
    {
        return ShanjianQueueService::statusText(
            (string)($data['queue_status'] ?? ''),
            (int)($data['queue_position'] ?? 0)
        );
    }

    public function setOriginalImagesAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getOriginalImagesAttr($value)
    {
        return $this->normalizeImageList($value);
    }

    public function setSelectedImagesAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getSelectedImagesAttr($value)
    {
        return $this->normalizeImageList($value);
    }

    public function setRewrittenImagesAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getRewrittenImagesAttr($value)
    {
        return $this->normalizeImageList($value);
    }

    public function setTikhubRawAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getTikhubRawAttr($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    public function setImageRewriteResultsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getImageRewriteResultsAttr($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /**
     * @return list<string> 相对路径（去域名），展示时由 lists/detail 补全
     */
    private function normalizeImageList(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $item = $item['url'] ?? $item['src'] ?? $item['path'] ?? '';
            }
            $item = trim((string)$item);
            if ($item === '') {
                continue;
            }
            $result[] = FileService::setFileUrl($item);
        }
        return array_values(array_unique($result));
    }
}
