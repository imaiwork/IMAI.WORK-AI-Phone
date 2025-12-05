<?php


namespace app\common\model;

use app\common\service\FileService;
use think\Model;

/**
 * 基础模型
 * Class BaseModel
 * @package app\common\model
 */
class BaseModel extends Model
{

    protected $hidden = ['delete_time'];
    /**
     * @notes 公共处理图片,补全路径
     * @param $value
     * @return string
     * @author 张无忌
     * @date 2021/9/10 11:02
     */
    public function getImageAttr($value)
    {
        return $value ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 公共图片处理,去除图片域名
     * @param $value
     * @return mixed|string
     * @author 张无忌
     * @date 2021/9/10 11:04
     */
    public function setImageAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }


    /**
     * 补全图片
     * @param $value
     * @return string
     */
    public function getLogoAttr($value)
    {

        return $value ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 公共图片处理,去除图片域名
     * @param $value
     * @return mixed|string
     * @author 张无忌
     * @date 2021/9/10 11:04
     */
    public function setLogoAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }

    /**
     * 补全图片
     * @param $value
     * @return string
     */
    public function getPicAttr($value)
    {

        return $value ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 公共图片处理,去除图片域名
     * @param $value
     * @return mixed|string
     * @author 张无忌
     * @date 2021/9/10 11:04
     */
    public function setPicAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }


    /**
     * 补全URL
     * @param $value
     * @return string
     */
    public function getUrlAttr($value)
    {

        return $value ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 公共URL处理,去除URL域名
     * @param $value
     * @return mixed|string
     * @author 张无忌
     * @date 2021/9/10 11:04
     */
    public function setUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }



    /**
     * 补全上传视频URL
     * @param $value
     * @return string
     */
    public function getUploadVideoUrlAttr($value)
    {

        return $value ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 公共上传视频URL处理,去除上传视频URL域名
     * @param $value
     * @return mixed|string
     * @author 张无忌
     * @date 2021/9/10 11:04
     */
    public function setUploadVideoUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }


    /**
     * 补全上传音频URL
     * @param $value
     * @return string
     */
    public function getUploadAudioUrlAttr($value)
    {

        return $value ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 公共上传音频URL处理,去除上传音频URL域名
     * @param $value
     * @return mixed|string
     * @author 张无忌
     * @date 2021/9/10 11:04
     */
    public function setUploadAudioUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }


    /**
     * 补全音频URL
     * @param $value
     * @return string
     */
    public function getAudioUrlAttr($value)
    {

        return $value ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 公共音频URL处理,去除音频URL域名
     * @param $value
     * @return mixed|string
     * @author 张无忌
     * @date 2021/9/10 11:04
     */
    public function setAudioUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }


    /**
     * 补全结果URL
     * @param $value
     * @return string
     */
    public function getResultUrlAttr($value)
    {

        return $value ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 公共结果URL处理,去除结果URL域名
     * @param $value
     * @return mixed|string
     * @author 张无忌
     * @date 2021/9/10 11:04
     */
    public function setResultUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }



    /**
     * 补全语音URL
     * @param $value
     * @return string
     */
    public function getVoiceUrlsAttr($value)
    {

        if (is_array($value))
        {
            foreach ($value as $k => $v)
            {
                $value[$k] = FileService::getFileUrl($v);
            }
        }
        else
        {
            $value = FileService::getFileUrl($value);
        }

        return $value;
    }

    /**
     * @notes 公共语音URL处理,去除语音URL域名
     * @param $value
     * @return mixed|string
     * @author 张无忌
     * @date 2021/9/10 11:04
     */
    public function setVoiceUrlsAttr($value)
    {
        if (is_array($value))
        {
            foreach ($value as $k => $v)
            {
                $value[$k] = FileService::setFileUrl($v);
            }
        }
        else
        {
            $value = FileService::setFileUrl($value);
        }
        return $value;
    }

    /**
     * 补全文件路径
     * @param $value
     * @return string
     */
    public function getFilePathAttr($value)
    {

        return $value ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 公共文件路径处理,去除文件路径域名
     * @param $value
     * @return mixed|string
     * @author 张无忌
     * @date 2021/9/10 11:04
     */
    public function setFilePathAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }


    /**
     * 补全文件路径
     * @param $value
     * @return string
     */
    public function getFileUrlAttr($value)
    {

        return $value ? FileService::getFileUrl($value) : '';
    }

    /**
     * @notes 公共文件路径处理,去除文件路径域名
     * @param $value
     * @return mixed|string
     * @author 张无忌
     * @date 2021/9/10 11:04
     */
    public function setFileUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }

    public function getClipResultUrlAttr($value)
    {

        return $value ? FileService::getFileUrl($value) : '';
    }
}
