<?php

namespace app\api\validate\wechat;

use app\common\validate\BaseValidate;

/**
 * 加微记录校验
 * Class AddRecordValidate
 * @package app\api\validate\wechat
 * @author Qasim
 */
class AddRecordValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require|integer|gt:0',
    ];

    protected $message = [
        'id.require' => '加微记录ID不能为空',
        'id.integer' => '加微记录ID错误',
        'id.gt' => '加微记录ID错误',
    ];


    /**
     * @notes 删除加微记录
     * @return Validate
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}
