<?php

namespace app\common\model\phoneAgent;

use app\common\model\BaseModel;

abstract class PhoneAgentBaseModel extends BaseModel
{
    protected $autoWriteTimestamp = false;

    protected function decodeJsonField($value): array
    {
        if (empty($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function encodeJsonField($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_string($value)) {
            return $value;
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
