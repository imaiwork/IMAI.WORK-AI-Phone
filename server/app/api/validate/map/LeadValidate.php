<?php

namespace app\api\validate\map;

use app\common\validate\BaseValidate;

class LeadValidate extends BaseValidate
{
    protected $rule = [
        'conversation_id' => 'max:64',
        'message_id'     => 'number|gt:0',
        'content'        => 'max:500',
        'query'          => 'max:255',
        'biz'            => 'max:100',
        'city'           => 'max:100',
        'region'         => 'max:100',
        'location_extra' => 'max:150',
        'types'          => 'max:100',
        'target_count'   => 'number|between:1,200',
        'page'           => 'number',
        'page_size'      => 'number|between:1,25',
    ];

    protected $message = [
        'conversation_id.require' => '会话ID不能为空',
        'conversation_id.max'     => '会话ID格式错误',
        'message_id.require'      => '消息ID不能为空',
        'message_id.number'       => '消息ID格式错误',
        'message_id.gt'           => '消息ID格式错误',
        'content.max'             => '消息内容最多500个字符',
        'query.max'               => '搜索内容最多255个字符',
        'biz.max'                 => '商家类型最多100个字符',
        'city.max'                => '城市最多100个字符',
        'region.max'              => '区域最多100个字符',
        'location_extra.max'      => '精确位置最多150个字符',
        'types.max'               => '高德类型最多100个字符',
        'target_count.number'     => '目标线索数格式错误',
        'target_count.between'    => '目标线索数范围为1-200',
        'page.number'             => '页码格式错误',
        'page_size.number'        => '每页数量格式错误',
        'page_size.between'       => '每页数量范围为1-25',
    ];

    public function sceneChat()
    {
        return $this->only([
            'conversation_id',
            'content',
            'query',
            'biz',
            'city',
            'region',
            'location_extra',
            'types',
            'target_count',
            'page',
            'page_size',
        ]);
    }

    public function sceneMessages()
    {
        return $this->only(['conversation_id'])->append('conversation_id', 'require');
    }

    public function sceneDelete()
    {
        return $this->only(['conversation_id'])->append('conversation_id', 'require');
    }

    public function sceneExport()
    {
        return $this->only(['message_id', 'conversation_id']);
    }
}
