<?php

namespace app\common\model\coze;

use app\common\model\BaseModel;
use app\common\service\FileService;

/**
 * Coze Agent 模型
 */
class CozeAgent extends BaseModel
{
    protected $name = 'coze_agent';

    // 来源类型常量
    const SOURCE_ADMIN = 0; // 后台
    const SOURCE_USER = 1;  // 用户

    // 类型常量
    const TYPE_AGENT = 1;   // 智能体
    const TYPE_WORKFLOW = 2; // 工作流

    // 权限类型常量
    const PERMISSIONS_UNLIMITED = 0; // 没有限制

    // 输出类型常量
    const STREAM_DIRECT = 0; // 直接输出
    const STREAM_STREAM = 1; // 流式

    // 扣费类型常量
    const DEDUCTION_TOKEN = 0; // token
    const DEDUCTION_COUNT = 1; // 按次

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
     * 获取类型文本
     */
    public static function getTypeText(int $type): string
    {
        $map = [
            self::TYPE_AGENT => '智能体',
            self::TYPE_WORKFLOW => '工作流',
        ];
        return $map[$type] ?? '未知类型';
    }

    /**
     * 获取权限类型文本
     */
    public static function getPermissionsText(int $permissions): string
    {
        $map = [
            self::PERMISSIONS_UNLIMITED => '没有限制',
        ];
        return $map[$permissions] ?? '未知权限';
    }

    /**
     * 获取输出类型文本
     */
    public static function getStreamText(int $stream): string
    {
        $map = [
            self::STREAM_DIRECT => '直接输出',
            self::STREAM_STREAM => '流式',
        ];
        return $map[$stream] ?? '未知输出类型';
    }

    /**
     * 获取扣费类型文本
     */
    public static function getDeductionText(int $deduction): string
    {
        $map = [
            self::DEDUCTION_TOKEN => 'token',
            self::DEDUCTION_COUNT => '按次',
        ];
        return $map[$deduction] ?? '未知扣费类型';
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



/**
 * 获取用户头像属性的方法
 * @param mixed $value 原始头像值
 * @return string 处理后的头像URL，如果原始值为空则返回空字符串
 */
    public function getAvatarAttr($value)
    {

    // 如果有值，则通过FileService获取文件的完整URL，否则返回空字符串
        return $value ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 公共图片处理,去除图片域名
     * @param $value
     * @date 2021/9/10 11:04
     */
    public function setAvatarAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }

    public function getBgImageAttr($value)
    {

        // 如果有值，则通过FileService获取文件的完整URL，否则返回空字符串
        return $value ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 公共图片处理,去除图片域名
     * @param $value
     * @return mixed|string

     */
    public function setBgImageAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }
}
