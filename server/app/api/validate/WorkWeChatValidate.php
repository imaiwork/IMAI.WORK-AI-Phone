<?php

namespace app\api\validate;

use app\common\validate\BaseValidate;


/**
 * 验证器
 * Class LoginValidate
 * @package app\api\validate
 */
class WorkWeChatValidate extends BaseValidate
{
    protected $rule = [
        'id'         => 'require',
        'port'       => 'require',
        'ip'         => 'require',
        'file_path'  => 'require',
        'count'      => 'require',
        'space_time' => 'require',
    ];


    protected $message = [
        'id.require'         => '主键参数缺失',
        'port.require'       => '端口参数缺失',
        'ip.require'         => 'ip参数缺失',
        'file_path.require'  => '文件路径参数缺失',
    ];
    protected $scene = [
        'add'          => [
            "port",
            "ip",
        ],
        'updateUser'   => [
            "port",
            'id'
        ],
        'delete'       => [
            "id",
        ],
        'detail'       => [
            "id",
            "port",
        ],
        'edit'         => [
            "id",
        ],
        'changeStatus' => [
            "id",
        ],
        'importList'   => [
            "file_path",
        ],
    ];
}
            