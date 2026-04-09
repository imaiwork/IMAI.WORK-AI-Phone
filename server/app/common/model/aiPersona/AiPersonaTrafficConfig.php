<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;

/**
 * ip人设获客与截流
 */
class AiPersonaTrafficConfig extends BaseModel
{


    public static function getTimesByType(int $personaType, int $accountType)
    {
        $maps = [
            1 => [
                3 => [
                    2 => [ //截流私信
                        '12:45-13:15' => '1,5', //是否同城，发送数量
                    ],
                    3 => [ // 截流留痕
                        '08:30-09:00' => '0,5',
                        '19:30-20:00' => '0,10',
                    ],
                ],
                4 => [
                    2 => [ //截流私信
                        '09:45-10:30' => '0,10',
                        '18:30-19:15' => '0,10',
                    ],
                    3 => [ // 截流留痕
                        '13:30-14:00' => '1,10',
                    ],
                ],
            ],
            2 => [
                3 => [
                    3 => [ // 截流留痕
                        '12:30-13:00' => '0,10',
                    ],
                ],
                4 => [
                    2 => [ //截流私信
                        '11:00-11:45' => '0,10',
                        '14:45-15:30' => '0,10',
                    ],
                ],
            ],
            3 => [
                3 => [
                    3 => [ // 截流留痕
                        '11:30-12:00' => '1,10',
                        '18:50-19:30' => '0,10',
                        '15:45-16:45' => '1,5',
                    ],
                ],
                4 => [
                    2 => [ //截流私信
                        '14:45-15:30' => '0,15',
                    ],
                    3 => [ // 截流留痕
                        '10:00-11:00' => '1,15',
                        '18:00-18:30' => '1,40',
                    ],
                ],
            ],
        ];

        return $maps[$personaType][$accountType] ?? [];
    }


    public static function getClueTimesByType(int $personaType, int $accountType)
    {
        $maps = [
            1 => [
                1 => []
            ],
            2 => [
                1 => [
                    '9:45-10:45',
                    '13:30-14:30',
                    '15:45-16:30',
                    '18:30-19:30',
                ]
            ],
            3 => [
                1 => []
            ],
        ];


        return $maps[$personaType][$accountType] ?? [];
    }


    public function setAcquireKeywordsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getAcquireKeywordsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }


    public function setInterceptKeywordsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getInterceptKeywordsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }


    public function setCommentScriptsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getCommentScriptsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setDmScriptsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getDmScriptsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
