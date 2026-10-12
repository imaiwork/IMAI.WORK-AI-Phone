<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

class Material extends BaseModel
{
    protected $name = 'ai_persona_material';
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    const MATERIAL_TYPE_VIDEO = 1;
    const MATERIAL_TYPE_IMAGE = 2;
    const MATERIAL_TYPE_MUSIC = 3;

    /** 背景音乐时长上限（秒）：闪剪合成侧硬限制 0-300 秒，超长会被回调拒绝 */
    const MUSIC_MAX_DURATION = 300;

    const USE_STATUS_DELETED = 0;
    const USE_STATUS_ENABLED = 1;
    const USE_STATUS_DISABLED = 2;

    const PUBLISH_MODE_MAKE_VIDEO = 1;
    const PUBLISH_MODE_DIRECT_SEND = 2;

    const SLICE_STATUS_NONE = 0;
    const SLICE_STATUS_PENDING = 1;
    const SLICE_STATUS_PROCESSING = 2;
    const SLICE_STATUS_SUCCESS = 3;
    const SLICE_STATUS_FAILED = 4;

    const SOURCE_TYPE_ORIGINAL = 'original';
    const SOURCE_TYPE_SLICE = 'slice';
    const KEEP_ORIGINAL_NO = 0;
    const KEEP_ORIGINAL_YES = 1;

    protected $json = [];
    protected $jsonAssoc = true;

    public function getFileUrlAttr($value)
    {
        return $value ? FileService::getFileUrl($value, '', true) : '';
    }

  
    public function setFileUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }


    public function getThumbnailUrlAttr($value)
    {
        return $value ? FileService::getFileUrl($value, '', true) : '';
    }


    public function setThumbnailUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }

    public static function getSliceStatusText(int $status): string
    {
        return match ($status) {
            self::SLICE_STATUS_PENDING => '待分割',
            self::SLICE_STATUS_PROCESSING => '分割中',
            self::SLICE_STATUS_SUCCESS => '分割成功',
            self::SLICE_STATUS_FAILED => '分割失败',
            default => '无需分割',
        };
    }

    public function getSliceStatusTextAttr($value, array $data): string
    {
        return self::getSliceStatusText((int)($data['slice_status'] ?? self::SLICE_STATUS_NONE));
    }

    public static function getSourceTypeText(string $sourceType, int $keepOriginal = 0): string
    {
        if ($sourceType === self::SOURCE_TYPE_SLICE) {
            return '切片素材';
        }
        return $keepOriginal === self::KEEP_ORIGINAL_YES ? '原始素材' : '完整素材';
    }

    /**
     * 素材库列表（api\lists\aiPersona\MaterialLists）在没传 use_status / slice_status 时的默认可见范围：
     * 可用素材（非切片，或切片已成功）+ 切割失败待清理的原片。
     *
     * 任何要跟列表对数的计数都必须套这一层。少了它，切片中、被停用的素材都会被数进去，
     * 出现「清单说 424 段、列表只有 116 段」「成品库说 1 条、点进去是空的」这种对不上的数。
     *
     * @param \think\db\Query|\think\Model $query
     */
    public static function applyDefaultVisible($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($ok) {
                $ok->where('use_status', self::USE_STATUS_ENABLED)
                    ->where(function ($src) {
                        $src->where('source_type', '<>', self::SOURCE_TYPE_SLICE)
                            ->whereOr('slice_status', self::SLICE_STATUS_SUCCESS);
                    });
            })->whereOr(function ($fail) {
                $fail->where('use_status', self::USE_STATUS_DISABLED)
                    ->where('slice_status', self::SLICE_STATUS_FAILED)
                    ->where('source_type', self::SOURCE_TYPE_ORIGINAL);
            });
        });
    }

    /**
     * 成品库直发必须有真实文件地址：空串、空白、NULL 都不能建单。
     */
    public static function rawFileUrl(mixed $material): string
    {
        if (is_array($material)) {
            return trim((string)($material['file_url'] ?? ''));
        }
        if ($material instanceof self) {
            return trim((string)$material->getData('file_url'));
        }
        if (is_object($material) && isset($material->file_url)) {
            return trim((string)$material->file_url);
        }
        return '';
    }

    public static function hasUsableFileUrl(mixed $material): bool
    {
        return self::rawFileUrl($material) !== '';
    }

    /**
     * @param \think\db\Query|\think\Model $query
     */
    public static function applyUsableFileUrl($query)
    {
        return $query->whereRaw("TRIM(IFNULL(file_url, '')) <> ''");
    }
}
