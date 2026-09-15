<?php

namespace app\common\model\auto;

use app\common\model\BaseModel;

class AutoDeviceConfig extends BaseModel {
    /**
     * 将human_image数组转换为JSON字符串
     * @param $value
     * @return string
     */
    public function setHumanImageAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 将human_image JSON字符串转换为数组
     * @param $value
     * @return array
     */
    public function getHumanImageAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 将clip_material数组转换为JSON字符串
     * @param $value
     * @return string
     */
    public function setClipMaterialAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 将clip_material JSON字符串转换为数组
     * @param $value
     * @return array
     */
    public function getClipMaterialAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 将image_material数组转换为JSON字符串
     * @param $value
     * @return string
     */
    public function setImageMaterialAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 将image_material JSON字符串转换为数组
     * @param $value
     * @return array
     */
    public function getImageMaterialAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}