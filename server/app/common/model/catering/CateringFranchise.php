<?php


namespace app\common\model\catering;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

/**
 * 招商项目管理模型
 * Class CateringFranchise
 * @package app\common\model\catering;
 */
class CateringFranchise extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    protected $name = 'catering_franchise';


    public function getCategoryTypeTextAttr($value, $data)
    {
        $categoryTypes = [
            1 => '本地生活',
            2 => '个人ip',
            3 => '企业服务'
        ];
        return $categoryTypes[$data['category_type']] ?? '';
    }

    public function getStatusTextAttr($value, $data)
    {
        return $data['status'] == 1 ? '启用' : '停用';
    }

    public function setTargetUsersAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }

    public function getTargetUsersAttr($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return [];
    }

    public function setTaskTypesAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }

    public function getTaskTypesAttr($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return [];
    }

    public function setDetailImagesAttr($value)
    {
        if (is_array($value)) {
            foreach ($value as &$image) {
                $image = !empty($image) ? FileService::setFileUrl($image) : '';
            }
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }

    public function getDetailImagesAttr($value)
    {
        if (is_string($value)) {
            $images = json_decode($value, true);
            if (!empty($images)) {
                foreach ($images as &$image) {
                    $image = !empty($image) ? FileService::getFileUrl($image) : '';
                }
            }
            return $images;
        }
        return [];
    }

    public function setDetailVideosAttr($value)
    {
        if (is_array($value)) {
            foreach ($value as &$video) {
                $video = !empty($video) ? FileService::setFileUrl($video) : '';
            }
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }

    public function getDetailVideosAttr($value)
    {
         if (is_string($value)) {
            $videos = json_decode($value, true);
            if (!empty($videos)) {
                foreach ($videos as &$video) {
                    $video = !empty($video) ? FileService::getFileUrl($video) : '';
                }
            }
            return $videos;
        }
        return [];
    }
}
