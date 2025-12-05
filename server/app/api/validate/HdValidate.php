<?php



namespace app\api\validate;

use app\common\enum\PayEnum;
use app\common\validate\BaseValidate;

class HdValidate extends BaseValidate
{
    protected $rule = [
        'image_url'      => 'require'
    ];


    protected $message = [
        'image_url.require'      => '图片url参数缺失'
    ];
    
    public function sceneImg2img()
    {

        return $this->only(['image_url']);
    }
}
