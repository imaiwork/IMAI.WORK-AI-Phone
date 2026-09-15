<?php

namespace app\common\model\auto;

use app\common\model\BaseModel;

class AutoDeviceClueConfig extends BaseModel {

    /**
     * 将keywords数组转换为JSON字符串
     * @param $value
     * @return string
     */
    public function setKeywordsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 将keywords JSON字符串转换为数组
     * @param $value
     * @return array
     */
    public function getKeywordsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
