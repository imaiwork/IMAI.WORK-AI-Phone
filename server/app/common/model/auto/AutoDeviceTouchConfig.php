<?php

namespace app\common\model\auto;

use app\common\model\BaseModel;

class AutoDeviceTouchConfig extends BaseModel
{

    public const ACCOUNT_EXEC_TIME_MAPS = [
        // 1 => [
        //     1 => '17:30-18:00'
        // ],
        3 => [
            1 => '15:30-16:00',
            2 => '16:00-16:30',
            3 => '16:30-17:00',
        ],
        4 => [
            1 => '10:30-11:00',
            2 => '11:00-11:30',
            3 => '11:30-12:00'
        ],
        // 5 => [
        //     1 => '18:30-19:00',
        //     2 => '19:00-19:30',
        //     3 => '19:30-20:00',
        // ],
    ];

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


    public function setCommentScreeningAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 将comment_screening JSON字符串转换为数组
     * @param $value
     * @return array
     */
    public function getCommentScreeningAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setTouchSpeechAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 将touch_speech JSON字符串转换为数组
     * @param $value
     * @return array
     */
    public function getTouchSpeechAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }


    public function setActionsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
    /**
     * 将actions JSON字符串转换为数组
     * @param $value
     * @return array
     */
    public function getActionsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setAccountAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
    /**
     * 将accounts JSON字符串转换为数组
     * @param $value
     * @return array
     */
    public function getAccountAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }


    public function setExecTimeAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
    /**
     * 将exec_time JSON字符串转换为数组
     * @param $value
     * @return array
     */
    public function getExecTimeAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
