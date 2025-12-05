<?php


namespace app\api\validate;

use app\common\enum\PayEnum;
use app\common\model\tools\Tools;
use app\common\service\ConfigService;
use app\common\validate\BaseValidate;

/**
 * 用户验证器
 * Class UserValidate
 * @package app\shopapi\validate
 */
class ToolsValidate extends BaseValidate
{

    protected $rule = [
        'tools_id' => 'require|checkTools',

        'value' => "require|checkPainting"
    ];


    protected $message = [
        'tools_id.require' => '主键参数丢失',
    ];

    protected $scene = [
        'delete'      => [
            'tools_id'
        ],
        'getToolsLog' => [
            'tools_id'
        ],
        'chat'        => [
            'tools_id',
            'value',
        ]
    ];

    protected function checkTools($value): bool
    {
        return !Tools::findOrEmpty($value)->isEmpty();
    }

    protected function checkPainting($value): bool|string
    {
        $config = config('sd');
        if (empty($config)) {
            return true;
        }
        $value = json_decode($value, true);
        if (empty($value['type'])) {
            return "必要参数type丢失";
        }
        if (!in_array($value['type'], ['ultra', 'sd3', 'core'])) {
            return "参数type类型异常";
        }
        foreach ($config as $v) {
            foreach ($v as $ks => $vs) {
                if ($vs['require'] === true && (empty($value[$ks]) || $value[$ks] == 0)) {
                    return "必要参数" . $ks . "丢失";
                }
                if ($value['type'] == "sd3") {
                    if (empty($value["prompt"])) {
                        return "prompt参数丢失";
                    }
                    if ($value['mode'] == "image-to-image") {
                        if (empty($value["image"]) || !isset($value['strength'])) {
                            return "类型为image-to-image必要参数 image , strength , prompt 丢失";
                        }
                    }
                    if ((!empty($value['output_format']) && ($value['output_format'] !== "png" && $value['output_format'] !== "jpeg"))) {
                        return "当前类型sd3, 只接受类型为jpeg 和 png的图片输出";
                    }
                }
            }
        }
        return true;
    }
}
