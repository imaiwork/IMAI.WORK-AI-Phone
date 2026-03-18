<?php
namespace app\adminapi\validate\cardcode;

use app\common\validate\BaseValidate;
use app\common\model\cardcode\CardPackage;

/**
 * 卡密套餐验证器
 * Class CardPackageValidate
 * @package app\adminapi\validate\cardcode
 */
class CardPackageValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkId',
        'name' => 'require|max:64',
        'tokens' => 'require|number|min:1',
        'status' => 'require|in:0,1',
        'sort' => 'number',
    ];

    protected $message = [
        'id.require' => '参数缺失',
        'name.require' => '请输入套餐名称',
        'name.max' => '套餐名称最多64个字符',
        'tokens.require' => '请输入包含算力点数',
        'tokens.number' => '算力必须为数字',
        'tokens.min' => '算力最少为1点',
        'status.require' => '选择状态',
        'status.in' => '状态值异常',
        'sort.number' => '排序必须是数字',
    ];

    public function sceneAdd()
    {
        return $this->only(['name', 'tokens', 'status', 'sort']);
    }

    public function sceneEdit()
    {
        return $this->only(['id', 'name', 'tokens', 'status', 'sort'])
            ->remove('name', 'require')
            ->remove('tokens', 'require')
            ->remove('status', 'require')
            ->append('id', 'checkEditEmpty');
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    protected function checkId($value, $rule, $data)
    {
        $package = CardPackage::findOrEmpty($value);
        if ($package->isEmpty()) {
            return '套餐不存在';
        }
        return true;
    }

    protected function checkEditEmpty($value, $rule, $data)
    {
        if (!isset($data['name']) && !isset($data['tokens']) && !isset($data['status']) && !isset($data['sort'])) {
            return '请至少填写一项需要修改的内容';
        }
        return true;
    }
}
