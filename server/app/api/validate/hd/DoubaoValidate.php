<?php

namespace app\api\validate\hd;

use app\common\validate\BaseValidate;

/**
 * 
 * Class DoubaoValidate
 * @package app\api\validate\hd
 * @author Qasim
 */
class DoubaoValidate extends BaseValidate
{

    protected $rule = [
        'task_id' => 'require',
        'text' =>'require',
        'image_url' => 'require',
        'aspect_ratio' => 'require',
    ];



    protected $message = [
        'task_id.require' => '任务id不能为空',
        'text.require' => '视频生成提示词不能为空',
        'image_url.require' => '视频生成图片地址不能为空',
        'aspect_ratio.require' => '视频生成图片比例不能为空',
    ];


    /**
     * @notes 文生视频
     * @return Validate
     */
    public function sceneTxt2video()
    {
        return $this->only([ 'text', 'aspect_ratio']);
    }

    /**
     * @notes 图生视频
     * @return Validate
     */
    public function sceneImg2video()
    {
        return $this->only(['image_url', 'aspect_ratio']);
    }

    /**
     * @notes 详情/删除
     * @return Validate
     */
    public function sceneDetail()
    {
        return $this->only(['id']);
    }
    
}
