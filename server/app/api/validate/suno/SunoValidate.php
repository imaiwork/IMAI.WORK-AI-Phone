<?php

namespace app\api\validate\suno;

use app\common\validate\BaseValidate;


/**
 * 验证器
 * Class LoginValidate
 * @package app\api\suno\validate
 */
class SunoValidate extends BaseValidate
{
    protected $rule = [
        'id'      => 'require',
        'ask'     => 'require',
        'clip_id' => 'require',
    ];


    protected $message = [
        'id.require'      => '主键参数缺失',
        'ask.require'     => '关键词参数缺失',
        'clip_id.require' => '关键参数缺失',
        'title.require' => '标题参数缺失',
        'tags.require' => '风格参数缺失',
    ];
    protected $scene = [
        'add'    => [
            "ask",
            "title",
            "tags",
        ],
        'createMusic'    => [
            "clip_id",
            "id",
        ],
        'delete' => [
            "id",
        ],
        'detail' => [
            "id",
        ],
        'edit'   => [
            "id",
        ],
    ];
}
            