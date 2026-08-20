<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;

class SvDeviceViral extends BaseModel
{
    public const PUBLISH_MEDIA_TYPE_VIDEO = 1;
    public const PUBLISH_MEDIA_TYPE_IMAGE_TEXT = 2;


    public function setGenerationTypesAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getGenerationTypesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setKeywordsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getKeywordsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setTimeConfigAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getTimeConfigAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setCustomDateAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getCustomDateAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
