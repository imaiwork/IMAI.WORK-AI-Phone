<?php

namespace app\api\validate\lianlian;

use app\common\validate\BaseValidate;


/**
 * 验证器
 * Class LoginValidate
 * @package app\api\lianlian\validate
 */
class LlCategoryInfoValidate extends BaseValidate
{
    protected $rule = [
        'id'              => 'require',
        'model_info_id'   => 'require',
        'file_path'       => 'require',
        'conversation_id' => 'require',
        'speed'           => 'in:0.25,4.0,1.0',
        'ask'             => 'require',
    ];


    protected $message = [
        'id.require'              => '主键参数缺失',
        'model_info_id.require'   => '模型信息主键参数缺失',
        'file_path.require'       => '文件参数缺失',
        'conversation_id.require' => '会话id参数缺失',
        'ask.require'             => '提问参数缺失',
    ];
    protected $scene = [
        'add'             => [
            "id",
        ],
        'delete'          => [
            "id",
        ],
        'detail'          => [
            "id",
        ],
        'edit'            => [
            "id",
        ],
        'toText'          => [
            "file_path",
        ],
        'createChat'      => [
            "model_info_id",
        ],
        'chat'            => [
            "conversation_id",
            "speed",
            "ask",
        ],
        'startChat'       => [
            "conversation_id",
            "speed",
        ],
        'analyse'         => [
            "conversation_id",
        ],
        'analyseDetail'   => [
            "conversation_id",
        ],
    ];
}
            