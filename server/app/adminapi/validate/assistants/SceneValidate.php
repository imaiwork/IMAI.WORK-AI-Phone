<?php
    
namespace app\adminapi\validate\assistants;

use app\common\validate\BaseValidate;


/**
 * 验证器
 * Class LoginValidate
 * @package app\adminapi\assistants\validate
 */
class SceneValidate extends BaseValidate
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
            "logo",
        ],
        'delete' => [
            "id",
        ],
        'detail' => [
            "id",
        ],
        'edit' => [
            "id",
            "logo",
        ],
    ];
}
            