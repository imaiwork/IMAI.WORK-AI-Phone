<?php



namespace app\api\validate;

use app\common\model\gptModel\GptModel;
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
        'assistants_id' => 'require',
        'temperature'   => 'require',
        'top_p'         => 'require',
        'logo'          => 'require',
        'status'        => 'require|in:1,0',
    ];


    protected $message = [
        'description.require'  => '描述参数缺失',


        'assistants_id.require' => '助手id参数丢失',
        'temperature.require'   => '温度参数丢失',
        'top_p.require'         => 'top_p参数丢失',
        'logo.require'          => 'logo参数丢失',
        'status.require'        => '状态参数丢失',
        'status.in'             => '状态参数错误',
    ];

    protected $scene = [
        'add'  => [
            'name',
            //            'description',
        ],
        'edit' => [
            'name',
            //            'description',
            //            'assistants_id',
            //            'temperature',
            //            'top_p',
            //            'logo',
            //            'status',
            //            'status',
        ]
    ];
}
