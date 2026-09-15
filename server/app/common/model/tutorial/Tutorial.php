<?php


namespace app\common\model\tutorial;

use app\common\model\BaseModel;
use app\common\service\FileService;
use think\model\concern\SoftDelete;

/**
 * 教程卡片管理模型
 * Class Tutorial
 * @package app\common\model\tutorial;
 */
class Tutorial extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    protected $name = 'tutorial';


    public function getCateNameAttr($value, $data)
    {
        return TutorialCategory::where('id', $data['tutorial_category_id'])->value('name');
    }

    public function setSubItemsAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode([]);
    }


    public function getSubItemsAttr($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return [];
    }
    public function setMainUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }
    public function getMainUrlAttr($value)
    {
        return $value ? FileService::getFileUrl($value) : '';
    }
}
