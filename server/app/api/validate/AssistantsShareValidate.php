<?php



namespace app\api\validate;

use app\common\validate\BaseValidate;

/**
 * 支付验证
 * Class PayValidate
 * @package app\api\validate
 */
class AssistantsShareValidate extends BaseValidate
{
    protected $rule = [
        'assistants_id'   => 'require',
        'to_ids'          => 'require',
        'expiration_date' => 'require',
        'id'              => 'require',
    ];


    protected $message = [
        'assistants_id.require'   => '机器人参数丢失',
        'to_ids.require'          => '被分享人参数缺失',
        'type.require'            => '类型参数缺失',
        'type.in'                 => '类型参数异常',
        'expiration_date.require' => '过期时间参数错误',

        'id.require' => '主键参数丢失参数错误',
    ];

    protected $scene = [
        'add'  => [
            'assistants_id',
            'to_ids',
            'expiration_date',
        ],
        'edit' => [
            'id',
            'assistants_id',
            'to_ids',
            'expiration_date',
        ]
    ];
}
