<?php
namespace app\common\model\suno;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

/**
 * 模型
 * Class Role
 * @package app\common\modelsuno\
 */
class Suno extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';


    public function getJsonInfoAttr($value)
    {
        if (empty($value)) {
            return [];
        }
        foreach ($value as &$v) {
            if (!empty($v['video_url'])) {
                $v['video_url'] = FileService::getFileUrl($v['video_url']);
            }
            if (!empty($v['audio_url'])) {
                $v['audio_url'] = FileService::getFileUrl($v['audio_url']);
            }
            if (!empty($v['image_url'])) {
                $v['image_url'] = FileService::getFileUrl($v['image_url']);
            }
        }
        return $value;
    }

}
            