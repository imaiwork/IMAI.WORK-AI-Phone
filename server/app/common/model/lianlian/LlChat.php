<?php

namespace app\common\model\lianlian;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;
use app\common\service\FileService;

/**
 * 模型
 * Class Role
 * @package app\common\modellianlian\
 */
class LlChat extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';


    public function getAskAudioAttr($value)
    {
        if($value){
            return FileService::getFileUrl($value);
        }
        return '';
    }

    public function getReplyAudioAttr($value)
    {
        if($value){
            return FileService::getFileUrl($value);
        }
        return '';
    }
    
    public function getPreliminaryAskAudioAttr($value)
    {
        if($value){
            return FileService::getFileUrl($value);
        }
        return '';
    }
}
            