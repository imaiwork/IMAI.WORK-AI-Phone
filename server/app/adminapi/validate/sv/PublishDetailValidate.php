<?php

namespace app\adminapi\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 发布设置校验
 * Class PublishValidate
 * @package app\api\validate\sv
 * @author Qasim
 */
class PublishDetailValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'publish_time' =>'require',
        'retry_time' => 'require',
    ];



    protected $message = [
        'id.require' => '任务id不能为空',
        'publish_time.require' => '请输入发布结束时间',
        'retry_time.require' => '请输入任务重试时间',
    ];




    /**
     * @notes 详情
     * @return Validate
     */
    public function sceneLists()
    {
        return $this->only(['id']);
    }

}
