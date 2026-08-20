<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;

/**
 * ip人设获客与截流
 */
class AiPersonaTrafficConfig extends BaseModel
{
    /**
     * Normalize note publish date filter for clue touch tasks.
     *
     * 0: unlimited, 1: within one day, 7: within seven days, 180: within half a year.
     * Legacy day values are mapped into the new filter buckets at runtime.
     */
    public static function normalizeContentPublishDay($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_string($value) && !is_numeric($value)) {
            return 0;
        }

        $day = (int)$value;
        if ($day <= 0) {
            return 0;
        }
        if ($day === 1) {
            return 1;
        }
        if ($day <= 7) {
            return 7;
        }
        if ($day <= 180) {
            return 180;
        }

        return 0;
    }

     /**
      * @desc 截流获客时间
      */
    public static function getTimesByType(int $personaType, int $accountType)
    {
        $maps = [
            1 => [
                3 => [
                    2 => [ //截流私信
                        '12:45-13:10' => '1,5', //是否同城，发送数量
                    ],
                    3 => [ // 截流留痕
                        '08:30-09:00' => '0,5',
                        '19:40-20:00' => '0,10',
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
                        '12:00-12:30' => '0,10',
                        '16:40-17:00' => '0,10',
                    ],
                ],
                4 => [
                    2 => [ //截流私信
                        '10:10-10:30' => '0,10',
                        '14:45-15:20' => '0,10',
                    ],
                    3 => [
                        '10:30-11:00' => '0,10',
                        '19:30-20:00' => '1,10',
                    ]
                ],
            ],
            3 => [
                3 => [
                    3 => [ // 截流留痕
                        '11:30-11:45' => '0,10',
                        '15:45-16:30' => '0,10',
                        '19:00-19:30' => '1,5',
                    ],
                ],
                4 => [
                    3 => [ // 截流留痕
                        '09:30-10:00' => '1,10',
                    ],
                ],
            ],
        ];

        return $maps[$personaType][$accountType] ?? [];
    }

     /**
     * @desc 获客线索时间
     */
    public static function getClueTimesByType(int $personaType, int $accountType)
    {
        $maps = [
            2 => [
                1 => [
                    '09:30-10:00',
                    '13:30-14:30',
                    '15:45-16:15',
                    '18:30-19:30'
                ]
            ]
        ];


        return $maps[$personaType][$accountType] ?? [];
    }

    /**
     * @desc 同城曝光时间
     */
    public static function getSameCityExposureTimesByType(int $personaType, int $accountType)
    {
        $maps = [
            3 => [
                4 => [
                    '07:00-08:00',
                    '09:15-09:30'
                ]
            ]
        ];
        return $maps[$personaType][$accountType] ?? [];
    }

    /**
     * @desc 同城截流时间
     */
    public static function getSameCityCutoffTimesByType(int $personaType, int $accountType)
    {
        $maps = [
            3 => [
                4 => [
                    '10:00-10:30',
                    '14:30-14:45'
                ]
            ],
        ];
        return $maps[$personaType][$accountType] ?? [];
    }

    /**
     * @desc 团购时间
     */
    public static function getGroupBuyTimesByType(int $personaType, int $accountType)
    {
        $maps = [
            3 => [
                4 => [
                    '08:00-08:30',
                    '18:00-18:30',
                    '20:00-20:30'
                ]
            ],
        ];
        return $maps[$personaType][$accountType] ?? [];
    }


    public function setClueKeywordsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getClueKeywordsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
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

    public function setGroupBuyConfigAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getGroupBuyConfigAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setSameCityConfigAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getSameCityConfigAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
