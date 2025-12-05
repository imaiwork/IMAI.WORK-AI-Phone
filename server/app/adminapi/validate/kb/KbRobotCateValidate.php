<?php


namespace app\adminapi\validate\kb;

use app\common\validate\BaseValidate;

/**
 * 机器人分类参数验证器
 */
class KbRobotCateValidate extends BaseValidate
{
    protected $rule = [
        'id'       => 'require|number',
        'name'      => 'require|min:2|max:30',
        'image'     => 'max:250',
        'sort'      => 'number',
        'is_enable' => 'require|in:0,1',
    ];

    protected $message = [
        'id.require'        => 'id参数缺失',
        'id.number'         => 'id参数必须为数字',
        'name.require'      => '请填写分类名称',
        'name.min'          => '分类名称不能小于2个字符',
        'name.max'          => '分类名称不能大于30个字符',
        'image.max'         => '图标的路径超出长度',
        'sort.number'       => '排序编号必须为数字',
        'is_enable.require' => '请选择分类状态',
        'is_enable.in'      => '分类状态选择异常: [0, 1]',

    ];

    public function sceneId(): KbRobotCateValidate
    {
        return $this->only(['id']);
    }

    public function sceneAdd(): KbRobotCateValidate
    {
        return $this->remove('id', 'require');
    }
}