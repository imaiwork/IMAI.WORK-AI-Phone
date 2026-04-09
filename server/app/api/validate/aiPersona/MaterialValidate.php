<?php

namespace app\api\validate\aiPersona;

use app\common\validate\BaseValidate;

class MaterialValidate extends BaseValidate
{
    protected $rule = [
        'persona_id' => 'require|number',
        'material_name' => 'require',
        'material_type' => 'require|in:1,2',
        'file_url' => 'require',
        'thumbnail_url' => 'require',
        'duration' => 'number',
        'width' => 'number',
        'height' => 'number',
        'use_status' => 'in:0,1,2',
        'publish_mode' => 'in:1,2',
        'items' => 'require|array',
    ];

    protected $message = [
        'persona_id.require' => '人设ID是必填项',
        'persona_id.number' => '人设ID必须是数字',
        'material_name.require' => '素材名称是必填项',
        'material_type.require' => '素材类型是必填项',
        'material_type.in' => '素材类型值只能是1或2',
        'file_url.require' => '文件URL是必填项',
        'thumbnail_url.require' => '缩略图URL是必填项',
        'duration.number' => '视频时长必须是数字',
        'width.number' => '宽度必须是数字',
        'height.number' => '高度必须是数字',
        'use_status.in' => '使用状态值只能是0、1或2',
        'publish_mode.in' => '发布模式值只能是1或2',
        'items.require' => '批量数据是必填项',
        'items.array' => '批量数据必须是数组',
    ];

    public function sceneAdd()
    {
        return $this->only(['persona_id', 'material_name', 'material_type', 'file_url', 'thumbnail_url', 'duration', 'width', 'height', 'use_status', 'publish_mode']);
    }

    public function sceneAddBatch()
    {
        return $this->only(['persona_id', 'items']);
    }

    public function sceneUpdate()
    {
        return $this->only(['id',  'use_status']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneDelete()
    {
        return $this->only(['id']);
    }
}
