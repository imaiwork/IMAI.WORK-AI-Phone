<?php

namespace app\api\validate\auto;

use app\common\validate\BaseValidate;

/**
 * 自动点赞评论任务校验
 * Class LikeReplyValidate
 * @package app\api\validate\auto
 * @author Qasim
 */
class LikeReplyValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'device_code' => 'require',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'device_code.require' => '请输入设备编码',
    ];


    /**
     * @notes 添加
     * @return Validate
     */
    public function sceneAdd()
    {
        return $this->only([ 'device_code']);
    }

    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['device_code']);
    }
    /**
     * @notes 删除
     * @return Validate
     */
    public function sceneDelete()
    {
        return $this->only(['device_code']);
    }

    /**
     * @notes 详情
     * @return Validate
     */
    public function sceneDetail()
    {
        return $this->only(['device_code']);
    }
}

