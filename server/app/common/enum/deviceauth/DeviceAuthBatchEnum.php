<?php

namespace app\common\enum\deviceauth;

/**
 * 设备CDK批次枚举
 */
class DeviceAuthBatchEnum
{
    const SOURCE_PLATFORM = 1;
    const SOURCE_IMPORT   = 2;

    const RULE_TYPE_LETTER = 1;
    const RULE_TYPE_NUMBER = 2;

    public static function getSourceDesc($source = true)
    {
        $desc = [
            self::SOURCE_PLATFORM => '中台生成',
            self::SOURCE_IMPORT   => '文件导入',
        ];
        if ($source === true) {
            return $desc;
        }
        return $desc[$source] ?? '';
    }
}
