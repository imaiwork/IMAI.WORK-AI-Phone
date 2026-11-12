<?php

namespace app\api\validate\general;

use app\common\validate\BaseValidate;

/**
 * 通用AI服务参数校验（接口参数规范，Controller 免登录接口不直接调用）
 * Class GeneralValidate
 */
class GeneralValidate extends BaseValidate
{
    protected $rule = [
        // device_code（两个免登录接口均必传，用于定位扣费主体 user_id）
        'device_code'     => 'require|max:128',
        // analyze 场景
        'image_url'       => 'max:500',
        'text'            => 'max:5000',
        'analyze_type'    => 'in:scene_recognize,object_extract,brand_analysis,content_summary,custom',
        'model'           => 'max:50',
        // ocr 场景
        'ocr_image_url'   => 'require|max:500',
        'lang'            => 'max:20',
    ];

    protected $message = [
        'device_code.require'     => '请提供设备号',
        'device_code.max'         => '设备号过长',
        'image_url.max'           => '图片地址过长',
        'analyze_type.in'         => '分析类型值不正确',
        'ocr_image_url.require'   => '请上传图片',
        'ocr_image_url.max'       => '图片地址过长',
    ];

    public function sceneAnalyze()
    {
        return $this->only(['device_code', 'image_url', 'text', 'analyze_type', 'model']);
    }

    public function sceneOcr()
    {
        return $this->only(['device_code', 'ocr_image_url', 'lang']);
    }
}
