<?php

namespace app\common\model\kb;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

/**
 * 数字人模型
 */
class KbDigital extends BaseModel
{
    use SoftDelete;

    protected string $deleteTime = 'delete_time';

    /**
     * @notes 获取器-数字人封面
     * @param $value
     * @return string
     * @author kb
     */
    public function getImageAttr($value): string
    {
        return !$value ? '' :FileService::getFileUrl($value);
    }

    /**
     * @notes 获取器-数字人头像
     * @param $value
     * @return string
     * @author kb
     */
    public function getAvatarAttr($value): string
    {
        return !$value ? '' :FileService::getFileUrl($value);
    }

    /**
     * @notes 获取器-宽屏人物待机视频
     * @param $value
     * @return string
     * @author kb
     */
    public function getWideStayVideoAttr($value): string
    {
        return !$value ? '' :FileService::getFileUrl($value);
    }

    /**
     * @notes 获取器-宽屏人物说话视频
     * @param $value
     * @return string
     * @author kb
     */
    public function getWideTalkVideoAttr($value): string
    {
        return !$value ? '' :FileService::getFileUrl($value);
    }

    /**
     * @notes 获取器-竖屏人物待机视频
     * @param $value
     * @return string
     * @author kb
     */
    public function getVerticalStayVideoAttr($value): string
    {
        return !$value ? '' :FileService::getFileUrl($value);
    }

    /**
     * @notes 获取器-竖屏人物说话视频
     * @param $value
     * @return string
     * @author kb
     */
    public function getVerticalTalkVideoAttr($value): string
    {
        return !$value ? '' :FileService::getFileUrl($value);
    }
}