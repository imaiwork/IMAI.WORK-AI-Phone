<?php


namespace app\adminapi\validate\notice;

use app\common\validate\BaseValidate;

/**
 * 通知验证
 * Class NoticeValidate
 * @package app\adminapi\validate\notice
 */
class NoticeValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
    ];

    protected $message = [
        'id.require' => '参数缺失',
    ];

    protected function sceneDetail()
    {
        return $this->only(['id']);
    }
}
