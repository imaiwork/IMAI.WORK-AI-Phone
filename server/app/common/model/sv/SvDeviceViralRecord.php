<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvDeviceViralRecord extends BaseModel {
    public const IMAGE_REWRITE_STATUS_NONE = 0;
    public const IMAGE_REWRITE_STATUS_WAIT = 1;
    public const IMAGE_REWRITE_STATUS_PROCESSING = 2;
    public const IMAGE_REWRITE_STATUS_SUCCESS = 3;
    public const IMAGE_REWRITE_STATUS_FAIL = 4;

    /** 状态：符合条件（爆款库可见成功记录） */
    public const STATUS_QUALIFIED = 4;
    /** 状态：3点兜底降级文案 */
    public const STATUS_DEADLINE_FALLBACK = 6;
    /** 状态：3点兜底对应的错误标记记录 */
    public const STATUS_FALLBACK_ERROR = 7;

    /** 文案类型：3点兜底降级文案 */
    public const COPYWRITING_TYPE_DEADLINE_FALLBACK = 6;
    /** 文案类型：3点兜底错误标记 */
    public const COPYWRITING_TYPE_FALLBACK_ERROR = 7;

    
    public function setGenerationTypesAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getGenerationTypesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setCopywritingAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getCopywritingAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setOriginalImagesAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getOriginalImagesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setRewrittenImagesAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getRewrittenImagesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setTikhubRawAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getTikhubRawAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setImageRewriteResultsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getImageRewriteResultsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
