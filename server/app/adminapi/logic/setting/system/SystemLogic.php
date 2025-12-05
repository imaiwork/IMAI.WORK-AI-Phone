<?php


namespace app\adminapi\logic\setting\system;


use app\common\logic\BaseLogic;

/**
 * Class SystemLogic
 * @package app\adminapi\logic\setting\system
 */
class SystemLogic extends BaseLogic
{

    /**
     * @notes 系统环境信息
     * @return \array[][]
     * @author 段誉
     * @date 2021/12/28 18:35
     */
    public static function getInfo(): array
    {
        $server = [
            ['param' => '服务器操作系统', 'value' => PHP_OS],
            ['param' => 'web服务器环境', 'value' => $_SERVER['SERVER_SOFTWARE']],
            ['param' => 'PHP版本', 'value' => PHP_VERSION],
        ];

        $env = [
            [
                'option' => 'PHP版本',
                'require' => '8.0版本以上',
                'status' => (int)compare_php('8.0.0'),
                'remark' => ''
            ]
        ];

        $auth = [
            [
                'dir' => '/runtime',
                'require' => 'runtime目录可写',
                'status' => (int)check_dir_write('runtime'),
                'remark' => ''
            ],
        ];

        return [
            'server' => $server,
            'env' => $env,
            'auth' => $auth,
        ];
    }
}
