<?php


namespace app\adminapi\validate\catering;

use app\common\validate\BaseValidate;
use app\common\model\catering\CateringFranchise;

/**
 * 招商项目管理验证
 * Class CateringFranchiseValidate
 * @package app\adminapi\validate\catering
 */
class CateringFranchiseValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkCateringFranchise',
        'category_type' => 'require|in:1,2,3',
        'title' => 'require|length:1,128',
        'status' => 'in:0,1',
    ];

    protected $message = [
        'id.require' => '项目id不能为空',
        'category_type.require' => '分类类型必须存在',
        'category_type.in' => '分类类型值不正确',
        'title.require' => '项目标题不能为空',
        'title.length' => '项目标题长度须在1-128位字符',
        'status.in' => '状态值不正确',
    ];

    public function sceneAdd()
    {
        return $this->remove(['id'])
            ->remove('id', 'require|checkCateringFranchise');
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneStatus()
    {
        return $this->only(['id', 'status']);
    }

    public function sceneEdit() {
         return $this->only(['id']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    public function checkCateringFranchise($value)
    {
        $franchise = CateringFranchise::findOrEmpty($value);
        if ($franchise->isEmpty()) {
            return '招商项目不存在';
        }
        return true;
    }
}
