<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class SvDySearchFilterHistory extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    public function setKeywordsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getKeywordsAttr($value)
    {
        return self::decodeJsonList($value);
    }

    public function setRemarksAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getRemarksAttr($value)
    {
        return self::decodeJsonList($value);
    }

    public function setMissScriptsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getMissScriptsAttr($value)
    {
        return self::decodeJsonList($value);
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private static function decodeJsonList($value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }
        if ($value === null || $value === '') {
            return [];
        }
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }
}
