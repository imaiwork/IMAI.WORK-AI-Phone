<?php

namespace app\api\validate\sv;

use app\api\validate\shanjian\Validate;
use app\common\validate\BaseValidate;

/**
 * 发布设置校验
 * Class PublishValidate
 * @package app\api\validate\sv
 * @author Qasim
 */
class MatrixPublishValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'name' => 'require',
        'accounts' =>  'require',
        //'video_setting_id' => 'require',
        'video_ids' => 'require',
        'publish_start' => 'require',
        'publish_end' => 'require',
        'status' =>'require',
        'media_type' => 'require',
        'date_type' => 'in:0,1'
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'name.require' => '请输入任务名称',
        'accounts.require' => '请选择账号',
        //'video_setting_id.require' => '请选择发布媒体集',
        'video_ids.require' => '请选择发布媒体集',
        'publish_start.require' => '请输入发布开始时间',
        'publish_end.require' => '请输入发布结束时间',
        'status.require' => '缺少状态参数',
        'media_type.require' => '缺少媒体类型参数',
        'date_type.in' => '时间类型值只能是0或1',
    ];


    /**
     * @notes 添加
     * @return Validate
     */
    public function sceneAdd()
    {
        return $this->only([ 'name']);
    }

    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['id', 'name', 'accounts', 'video_setting_id']);
    }

    /**
     * @notes 状态修改
     * @return Validate
     */
    public function sceneChange()
    {
        return $this->only(['id', 'status']);
    }

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
    /**
     * @notes 详情
     * @return Validate
     */
    public function sceneRecordList()
    {
        return $this->only(['id']);
    }
}
