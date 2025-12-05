<?php

namespace app\adminapi\validate\kb;

use app\common\validate\BaseValidate;

/**
 * 机器人广场参数验证器
 */
class KbSquareValidate extends BaseValidate
{
    protected $rule = [
        'id'      => 'require|number',
        'cid'     => 'number',
        'sort'    => 'number|max:9999999999',
        'is_show' => 'require|in:0,1',
    ];

    protected $message = [
        'id.require'      => 'id参数缺失',
        'id.number'       => 'id参数必须为数字',
        'cid.number'      => 'id参数必须为数字',
        'sort.number'     => '排序编号必须为数字',
        'is_show.require' => '请选择显示状态',
        'is_show.in'      => '显示状态选择异常: [0, 1]'
    ];

    public function sceneId(): KbSquareValidate
    {
        return $this->only(['id']);
    }

    public function sceneStatus(): KbSquareValidate
    {
        return $this->only(['id', 'is_show']);
    }

    public function sceneSort(): KbSquareValidate
    {
        return $this->only(['id', 'sort']);
    }
}