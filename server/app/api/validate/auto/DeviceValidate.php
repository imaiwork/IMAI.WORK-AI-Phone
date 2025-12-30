<?php

namespace app\api\validate\auto;

use app\common\validate\BaseValidate;

/**
 * 设备自动任务校验
 * Class DeviceValidate
 * @package app\api\validate\auto
 * @author Qasim
 */
class DeviceValidate extends BaseValidate
{

    protected $rule = [
        'id' => 'require',
        'device_code' => 'require',
        'human_image' => 'require|array',
        'clip_material' =>  'require|array',
        'image_material' => 'require|array',
        'clue_theme' => 'require|string|max:255',
        'video_theme' => 'require|string|max:255',
        'text_theme' => 'require|string|max:255',
    ];



    protected $message = [
        'id.require' => '请输入主键ID',
        'device_code.require' => '请输入设备编码',
        'human_image.require' => '请选择数字人形象',
        'human_image.array' => '数字人形象必须是数组',
        'clip_material.require' => '请选择剪辑素材',
        'clip_material.array' => '剪辑素材必须是数组',
        'image_material.require' => '请选择图文内容',
        'image_material.array' => '图文内容必须是数组',
        'clue_theme.require' => '请输入线索主题',
        'clue_theme.max' => '线索主题最多255个字符',
        'video_theme.require' => '请输入视频营销主题',
        'video_theme.max' => '视频营销主题最多255个字符',
        'text_theme.require' => '请输入图文营销主题',
        'text_theme.max' => '图文营销主题最多255个字符',
    ];


    /**
     * @notes 添加
     * @return Validate
     */
    public function sceneAdd()
    {
        return $this->only([ 'device_code', 'character_image', 'clip_material', 'image_material', 'customer_data', 'video_theme', 'text_content']);
    }

    /**
     * @notes 更新
     * @return Validate
     */
    public function sceneUpdate()
    {
        return $this->only(['id', 'device_code', 'character_image', 'clip_material', 'image_material', 'customer_data', 'video_theme', 'text_content']);
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

