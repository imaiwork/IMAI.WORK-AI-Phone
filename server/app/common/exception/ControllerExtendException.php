<?php


declare(strict_types=1);

namespace app\common\exception;

use think\Exception;

/**
 * 控制器继承异常
 * Class ControllerExtendException
 * @package app\common\exception
 */
class ControllerExtendException extends Exception
{
    /**
     * 构造方法
     * @access public
     * @param string $message
     * @param string $model
     * @param array $config
     */
    public function __construct(string $message, string $model = '', array $config = [])
    {
        $this->message = '控制器需要继承模块的基础控制器：' . $message;
        $this->model = $model;
    }
}
