<?php



namespace app\adminapi\validate\assistants;

use app\common\validate\BaseValidate;

/**
 * 支付验证
 * Class PayValidate
 * @package app\api\validate
 */
class AssistantsValidate extends BaseValidate
{
    protected $rule = [
        'name'          => 'require',
        'description'   => 'require',
        'instructions'  => 'require',
        'id'            => 'require',
        'temperature'   => 'require',
        'top_p'         => 'require',
        'logo'          => 'require',
        'scene_id'      => 'require',
        'status'        => 'require|in:1,0',
    ];


    protected $message = [
        'model.require'        => '名称参数缺失',
        'name.require'         => '名称参数缺失',
        'description.require'  => '描述参数缺失',
        'instructions.require' => '指令参数错误',


        'id.require'            => '助手id参数丢失',
        'temperature.require'   => '温度参数丢失',
        'top_p.require'         => 'top_p参数丢失',
        'logo.require'          => 'logo参数丢失',
        'status.require'        => '状态参数丢失',
        'status.in'             => '状态参数错误',
        'scene_id.require'      => '场景值丢失',
    ];

    protected $scene = [
        'add'  => [
            'name',
            'description',
            'instructions',
            'scene_id',
            'logo',
        ],
        'edit' => [
            'id',
            'name',
            'description',
            'instructions',
            'logo',
            'scene_id',
        ]
    ];
}
