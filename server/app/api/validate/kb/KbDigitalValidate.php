<?php


namespace app\api\validate\kb;

use app\common\validate\BaseValidate;

class KbDigitalValidate extends BaseValidate
{

    protected $rule = [
        'id'                  => 'require|number',
        'name'                => 'require|min:2|max:100',
        'avatar'              => 'require|max:255',
        'image'               => 'require|max:255',
        'wide_stay_video'     => 'require|max:255',
        'wide_talk_video'     => 'require|max:255',
        'vertical_stay_video' => 'require|max:255',
        'vertical_talk_video' => 'require|max:255',
        'dubbing'             => 'require|max:100',
        'idle_time'           => 'number|max:999999999',
        'idle_reply'          => 'max:30000'
    ];

    protected $message = [
        'id.require'                  => 'id参数',
        'name.require'                => '请填写数字人名称',
        'name.min'                    => '数字人名称不能少于2个字符',
        'name.max'                    => '数字人名称不能大于100个字符',
        'avatar.require'              => '请设置数字人头像',
        'image.require'               => '请设置数字人封面',
        'wide_stay_video.require'     => '请设置宽屏人物待机视频',
        'wide_talk_video.require'     => '请设置宽屏人物说话视频',
        'vertical_stay_video.require' => '请设置竖屏人物待机视频',
        'vertical_talk_video.require' => '请设置竖屏人物说话视频',
        'idle_time.max'               => '自定义闲时时间不能超出9位数'
    ];

    public function sceneId(): KbDigitalValidate
    {
        return $this->only(['id']);
    }

    public function sceneAdd(): KbDigitalValidate
    {
        return $this->only(['name', 'avatar', 'image']);
    }
}