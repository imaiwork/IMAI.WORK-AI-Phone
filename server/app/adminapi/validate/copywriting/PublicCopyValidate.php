<?php

namespace app\adminapi\validate\copywriting;

use app\common\validate\BaseValidate;

class PublicCopyValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer|gt:0',
        'title' => 'max:30',
        'content' => 'max:1200',
        'topic' => 'max:200',
        'ui_type' => 'in:1,2',
        'library_type' => 'in:1,2',
        'driver_type' => 'in:0,1,2,3',
        'industry' => 'max:16',
        'sort' => 'integer',
        'enabled' => 'in:0,1',
    ];

    protected $message = [
        'id.require' => '缺少记录id',
        'title.max' => '标题最多30字',
        'content.max' => '正文最多1200字',
        'topic.max' => '话题最多200字',
        'ui_type.in' => '请选择文案用途',
        'library_type.in' => '请选择入库分类',
        'driver_type.in' => '请选择入库分类',
        'industry.max' => '行业最多16字',
    ];

    public function sceneAdd()
    {
        return $this->only(['title', 'content', 'topic', 'ui_type', 'library_type', 'driver_type', 'industry', 'sort', 'enabled'])
            ->append('ui_type', 'require')
            ->append('library_type', 'require');
    }

    public function sceneUpdate()
    {
        return $this->only(['id', 'title', 'content', 'topic', 'ui_type', 'library_type', 'driver_type', 'industry', 'sort', 'enabled']);
    }

    public function sceneId()
    {
        return $this->only(['id']);
    }
}
