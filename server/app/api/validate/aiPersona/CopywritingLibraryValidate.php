<?php

namespace app\api\validate\aiPersona;

use app\common\validate\BaseValidate;

class CopywritingLibraryValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number',
        'ids' => 'require|array',
        'persona_id' => 'require|number',
        'library_type' => 'require|in:1,2',
        'driver_type' => 'in:0,1,2,3',
        'title' => 'max:2000',
        'content' => 'max:10000',
        'topic' => 'max:1000',
        'sort' => 'number',
        'status' => 'in:0,1',
        'items' => 'require|array',
        'file' => 'require|max:2000',
    ];

    protected $message = [
        'id.require' => '请输入文案ID',
        'id.number' => '文案ID必须是数字',
        'ids.require' => '请选择要删除的文案',
        'ids.array' => '删除ID必须是数组',
        'persona_id.require' => '请输入人设ID',
        'persona_id.number' => '人设ID必须是数字',
        'library_type.require' => '请选择文案库类型',
        'library_type.in' => '文案库类型值不正确',
        'driver_type.in' => '视频驱动文案类型值不正确',
        'title.max' => '标题长度不能超过2000个字符',
        'content.max' => '内容长度不能超过10000个字符',
        'topic.max' => '话题长度不能超过1000个字符',
        'sort.number' => '排序值必须是数字',
        'status.in' => '状态值不正确',
        'items.require' => '请填写要添加的文案',
        'items.array' => '文案列表必须是数组',
        'file.require' => '请提供导入文件链接',
        'file.max' => '导入文件链接不能超过2000个字符',
    ];

    public function sceneAdd()
    {
        return $this->only(['persona_id', 'library_type', 'driver_type', 'title', 'content', 'topic', 'sort', 'status'])
            ->append('driver_type', 'require');
    }

    public function sceneBatchAdd()
    {
        return $this->only(['persona_id', 'library_type', 'driver_type', 'items'])
            ->append('driver_type', 'require');
    }

    public function sceneUpdate()
    {
        return $this->only(['id', 'library_type', 'driver_type', 'title', 'content', 'topic', 'sort', 'status']);
    }

    public function sceneDetail()
    {
        return $this->only(['id']);
    }

    public function sceneDelete()
    {
        return $this->only(['ids']);
    }

    public function sceneImport()
    {
        return $this->only(['persona_id', 'library_type', 'driver_type', 'file'])
            ->append('driver_type', 'require');
    }
}
