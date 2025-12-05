<?php



namespace app\adminapi\validate\gptFile;

use app\common\validate\BaseValidate;

/**
 * 支付验证
 * Class PayValidate
 * @package app\api\validate
 */
class GptFileValidate extends BaseValidate
{
    protected $rule = [
        'file_path' => 'require',
        'file_name' => 'require',
        'purpose'   => 'require|in:assistants,vision,batch,fine-tune',
    ];


    protected $message = [
        'file_path.require' => '文件路径参数缺失',
        'file_name.require' => '文件名称参数缺失',
        'purpose.in'        => '目的参数错误',
        'purpose.require'   => '目的参数缺失'
    ];

    protected $scene = [
        'add' => [
            'file_path',
            'file_name',
            'purpose',
        ]
    ];
}
