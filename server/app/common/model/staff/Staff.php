<?php

namespace app\common\model\staff;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;
use app\common\service\FileService;

class Staff extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';


    /**
     * @notes 获取内容
     * @param $value
     * @return string
     * @author 段誉
     * @date 2024/1/7 15:48
     */
    public function getContentAttr($value): string
    {

        // 使用正则提取 src 内容并替换
        $value = preg_replace_callback(
            '/<img[^>]+src=["\']([^"\']+)["\']/i', // 匹配 img 标签中的 src 属性
            function ($matches) {
                $src = $matches[1];

                // 如果 src 不以 http 或 https 开头，则添加 https
                if (!preg_match('/^https?:\/\//i', $src)) {

                    $src = FileService::getFileUrl($src);
                }

                // 返回替换后的 img 标签
                return str_replace($matches[1], $src, $matches[0]);
            },
            $value
        );

        return $value;
    }


    /**
     * @notes 设置内容
     * @param $value
     * @return string
     * @author 段誉
     * @date 2024/1/7 15:48
     */
    public function setContentAttr($value): string
    {

        // 使用正则提取 src 内容并替换
        $value = preg_replace_callback(
            '/<img[^>]+src=["\']([^"\']+)["\']/i', // 匹配 img 标签中的 src 属性
            function ($matches) {
                $src = $matches[1];

                // 如果 src 不以 http 或 https 开头，则添加 https
                if (preg_match('/^https?:\/\//i', $src)) {

                    $src = FileService::setFileUrl($src);
                }

                // 返回替换后的 img 标签
                return str_replace($matches[1], $src, $matches[0]);
            },
            $value
        );

        return $value;
    }
}
