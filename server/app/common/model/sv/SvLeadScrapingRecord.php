<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;
use app\common\service\FileService;

class SvLeadScrapingRecord extends BaseModel {
    use SoftDelete;
    protected $deleteTime = 'delete_time';


  /**
     * @notes 公共处理图片,补全路径
     * @param $value
     * @return string
     * @author 张无忌
     * @date 2021/9/10 11:02
     */
    public function getAvatarAttr($value)
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
    public function setAvatarAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }

}
