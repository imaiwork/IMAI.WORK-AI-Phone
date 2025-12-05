<?php

namespace app\adminapi\validate\sv;

use app\common\validate\BaseValidate;

/**
 * 发布设置校验
 * Class PublishValidate
 * @package app\api\validate\sv
 * @author Qasim
 */
class PublishValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'name' => 'require',
        'accounts' =>  'require',
        'video_setting_id' => 'require',
        'publish_start' => 'require',
        'publish_end' => 'require',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'name.require' => '请输入任务名称',
        'accounts.require' => '请选择账号',
        'video_setting_id.require' => '请选择视频集',
        'publish_start.require' => '请输入发布开始时间',
        'publish_end.require' => '请输入发布结束时间',
    ];



    /**
     * @notes 删除
     * @return Validate
     */
    public function sceneDelete()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 详情
     * @return Validate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }

}
