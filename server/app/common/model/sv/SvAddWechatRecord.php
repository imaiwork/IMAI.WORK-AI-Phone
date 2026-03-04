<?php

namespace app\common\model\sv;

use app\common\model\BaseModel;
use app\common\service\FileService;

class SvAddWechatRecord extends BaseModel {




     /**
     * @notes 公共处理图片,补全路径
     * @param $value
     * @return string
     * @author 张无忌
     * @date 2021/9/10 11:02
     */
    public function getWechatAvatarAttr($value)
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
    public function setWechatAvatarAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }
}
