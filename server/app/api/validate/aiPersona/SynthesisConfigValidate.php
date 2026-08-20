<?php

namespace app\api\validate\aiPersona;

use app\common\validate\BaseValidate;

class SynthesisConfigValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number',
        'persona_id' => 'require|number',
        'generation_types' => 'array',
        'template_config' => 'array',
        'visual_material_source' => 'in:1,2,3',
        'copywriting_source' => 'in:1,2,3,4',
        'library_use_mode' => 'in:1,2',
        'library_reuse_mode' => 'in:1,2',
        'work_mode' => 'in:1,2',
        'product_use_mode' => 'in:1,2',
        'product_reuse_mode' => 'in:1,2',
        'video_cover_type' => 'in:1,2,3',
        'pic' => 'max:255',
        'music_source' => 'in:1,2,3',
        'music_volume' => 'float|between:0,1',
        'speech_rate' => 'float|between:0.5,2',
        'ids' => 'require|array',
    ];

    protected $message = [
        'id.require' => '配置ID是必填项',
        'id.number' => '配置ID必须是数字',
        'persona_id.require' => '人设ID是必填项',
        'persona_id.number' => '人设ID必须是数字',
        'generation_types.array' => '生成类型必须是数组',
        'template_config.array' => '视频模板配置必须是对象',
        'visual_material_source.in' => '画面素材来源值只能是1、2或3',
        'copywriting_source.in' => '文案来源值只能是1、2、3或4',
        'library_use_mode.in' => '文案库使用方式值只能是1或2',
        'library_reuse_mode.in' => '文案库重复规则值只能是1或2',
        'work_mode.in' => '工作方式值只能是1或2',
        'product_use_mode.in' => '成品库使用方式值只能是1或2',
        'product_reuse_mode.in' => '成品库随机规则值只能是1或2',
        'video_cover_type.in' => '视频封面类型值只能是1、2或3',
        'pic.max' => '视频封面长度不能超过255个字符',
        'music_source.in' => '背景音乐来源值只能是1、2或3',
        'music_volume.float' => '背景音乐音量必须是数字',
        'music_volume.between' => '背景音乐音量必须在0到1之间',
        'speech_rate.float' => '数字人语速必须是数字',
        'speech_rate.between' => '数字人语速必须在0.5到2之间',
        'ids.require' => '删除ID是必填项',
        'ids.array' => '删除ID必须是数组',
    ];

    public function sceneAdd()
    {
        return $this->only([
            'persona_id',
            'generation_types',
            'template_config',
            'visual_material_source',
            'copywriting_source',
            'library_use_mode',
            'library_reuse_mode',
            'work_mode',
            'product_use_mode',
            'product_reuse_mode',
            'video_cover_type',
            'pic',
            'music_source',
            'music_volume',
            'speech_rate',
        ])->append('generation_types', 'require');
    }

    public function sceneUpdate()
    {
        return $this->only([
            'id',
            'generation_types',
            'template_config',
            'visual_material_source',
            'copywriting_source',
            'library_use_mode',
            'library_reuse_mode',
            'work_mode',
            'product_use_mode',
            'product_reuse_mode',
            'video_cover_type',
            'pic',
            'music_source',
            'music_volume',
            'speech_rate',
        ]);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneDelete()
    {
        return $this->only(['ids']);
    }
}
