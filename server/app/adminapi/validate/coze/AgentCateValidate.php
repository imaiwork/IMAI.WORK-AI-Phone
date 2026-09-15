<?php
    
namespace app\adminapi\validate\coze;

use app\common\validate\BaseValidate;


/**
 * 验证器
 * Class LoginValidate
 */
class AgentCateValidate extends BaseValidate
{
    protected $rule = [
        'id'    => 'require',
        'name'    => 'require',
        'logo'    => 'require',
    ];


    protected $message = [
        'id.require'      => '主键参数缺失',
        'name.require'      => '名称参数缺失',
        'logo.require'      => 'logo参数缺失',
    ];
    protected $scene = [
        'add' => [
            "name",
        ],
        'delete' => [
            "id",
        ],
        'detail' => [
            "id",
        ],
        'edit' => [
            "id",
        ],
    ];
}
            