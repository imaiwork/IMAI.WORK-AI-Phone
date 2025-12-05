<?php


namespace app\api\validate;


use app\common\validate\BaseValidate;

/**
 * 用户验证器
 * Class UserValidate
 * @package app\shopapi\validate
 */
class UserValidate extends BaseValidate
{

    protected $rule = [
        'code' => 'require',
    ];

    protected $message = [
        'code.require' => '参数缺失',
    ];


    /**
     * @notes 获取小程序手机号场景
     * @return UserValidate
     * @author 段誉
     * @date 2022/9/21 16:44
     */
    public function sceneGetMobileByMnp()
    {
        return $this->only(['code']);
    }


    /**
     * @notes 绑定/变更 手机号
     * @return UserValidate
     * @author 段誉
     * @date 2022/9/21 17:37
     */
    public function sceneBindMobile()
    {
        return $this->only(['mobile', 'code']);
    }
}
