<?php

namespace app\api\validate\team;

use app\common\validate\BaseValidate;

/**
 * 团队制卡校验
 * Class TeamCardValidate
 * @package app\api\validate\team
 */
class TeamCardValidate extends BaseValidate
{
    protected $rule = [
        'package_id' => 'require|number',
        'count' => 'require|number|gt:0|elt:500',
        'valid_start_time' => 'require|number|gt:0',
        'valid_end_time' => 'require|number|gt:0',
        'id' => 'require|number',
        'to_user_id' => 'require|number|gt:0',
    ];

    protected $message = [
        'package_id.require' => '请选择卡密套餐',
        'count.require' => '请输入生卡数量',
        'count.gt' => '生卡数量必须大于0',
        'count.max' => '每次最多生成99张卡密',
        'valid_start_time.require' => '请选择生效开始时间',
        'valid_start_time.gt' => '请选择生效开始时间',
        'valid_end_time.require' => '请选择生效结束时间',
        'valid_end_time.gt' => '请选择生效结束时间',
        'id.require' => '请选择卡密',
        'to_user_id.require' => '请选择接收成员',
        'to_user_id.gt' => '请选择接收成员',
    ];

    public function sceneGenerate()
    {
        return $this->only(['count', 'valid_start_time', 'valid_end_time']);
    }

    public function sceneTransfer()
    {
        return $this->only(['id', 'to_user_id']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}
