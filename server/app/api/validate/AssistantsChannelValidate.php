<?php



namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 支付验证
 * Class PayValidate
 * @package app\api\validate
 */
class AssistantsChannelValidate extends BaseValidate
{
    protected $rule = [
        'assistants_id'   => 'require',
        'name'            => 'require',
        'id'              => 'require',
        'type'            => 'require',
    ];


    protected $message = [
        'assistants_id.require'   => '机器人参数丢失',
        'type.require'            => '类型参数缺失',
        'name.require'            => '名称必填',
        'id.require' => '主键参数丢失参数错误',
    ];

    protected $scene = [
        'add'  => [
            'assistants_id',
            'type',
            'name',
        ],
        'edit' => [
            'id',
            'assistants_id',
            'type',
            'name',
        ]
    ];
}
