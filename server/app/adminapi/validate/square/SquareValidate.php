<?php

namespace app\adminapi\validate\square;

use app\common\validate\BaseValidate;

class SquareValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'name' => 'max:24',
        'desc' => 'max:60',
        'enabled' => 'in:0,1',
        'sort' => 'integer',
        'group_id' => 'require|integer|gt:0',
        'kind' => 'in:1,2',
        'url' => 'max:1000',
        'cover_url' => 'max:1000',
        'duration_sec' => 'integer|egt:0',
        'items' => 'array',
    ];

    protected $message = [
        'id.require' => '缺少记录id',
        'name.require' => '名称不能为空',
        'name.max' => '名称超长',
        'desc.max' => '备注最多60字',
        'group_id.require' => '请选择分组',
        'group_id.gt' => '请选择分组',
        'kind.in' => '素材类型只能是视频或图片',
    ];

    public function sceneGroupAdd()
    {
        $this->message['name.require'] = '分组名称不能为空';
        $this->message['name.max'] = '分组名称最多16字';
        return $this->only(['name', 'desc', 'enabled', 'sort'])->append('name', 'require|max:16');
    }

    public function sceneGroupUpdate()
    {
        $this->message['name.max'] = '分组名称最多16字';
        return $this->only(['id', 'name', 'desc', 'enabled', 'sort'])->append('name', 'max:16');
    }

    public function sceneId()
    {
        return $this->only(['id']);
    }

    public function sceneAssetAdd()
    {
        // 批量入库只带 group_id + items，不能按建分组去要求顶层 name
        return $this->only(['group_id', 'kind', 'name', 'url', 'cover_url', 'duration_sec', 'enabled', 'sort', 'items']);
    }

    public function sceneAssetUpdate()
    {
        // 列表上架/下架只传 id + enabled，分组沿用原记录，不能再要求重选
        return $this->only(['id', 'group_id', 'kind', 'name', 'url', 'cover_url', 'duration_sec', 'enabled', 'sort'])
            ->remove('group_id', 'require');
    }
}