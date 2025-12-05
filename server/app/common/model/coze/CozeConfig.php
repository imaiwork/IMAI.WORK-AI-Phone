<?php

namespace app\common\model\coze;

use app\common\model\BaseModel;

/**
 * Coze 配置模型
 */
class CozeConfig extends BaseModel
{
    protected $name = 'coze_config';

    // 来源类型常量
    const SOURCE_ADMIN = 0; // 后台
    const SOURCE_USER = 1;  // 用户

    /**
     * 获取来源文本
     */
    public static function getSourceText(int $source): string
    {
        $map = [
            self::SOURCE_ADMIN => '后台',
            self::SOURCE_USER => '用户',
        ];
        return $map[$source] ?? '未知来源';
    }

    /**
     * 创建时间格式化
     */
    public function getCreateTimeAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 更新时间格式化
     */
    public function getUpdateTimeAttr($value)
    {
        if (empty($value)) {
            return $value;
        }
        return date('Y-m-d H:i:s', $value);
    }
}


